<?php

require_once './vendor/autoload.php';

use DestinyLab\Swetest;
// $swetest = new Swetest('/usr/local/bin/');

// planetary lists:
// d (default) main factors 0123456789mtABCcg
// p main factors as above, plus main asteroids DEFGHI
// h ficticious factors J..X
// a all factors
// (the letters above can only appear as a single letter)
// single body numbers/letters:
// 0 Sun
// 1 Moon
// 2 Mercury
// 3 Venus
// 4 Mars
// 5 Jupiter
// 6 Saturn
// 7 Uranus
// 8 Neptune
// 9 Pluto
// m mean lunar node
// t true lunar node
// n nutation
// o obliquity of ecliptic
// q delta t
// y time equation
// b ayanamsha
// A mean lunar apogee (Lilith, Black Moon) 
// B osculating lunar apogee 
// c intp. lunar apogee 
// g intp. lunar perigee 
// C Earth (in heliocentric or barycentric calculation)
// For planets Jupiter to Pluto the center of body (COB) can be
// calculated using the additional parameter -cob
// $opt = [
//     'geopos' => '51,0,0',   // location
//     'head',
//     'g' => '\;',            // separator
//     'f' => 'PTls',          // format
//     'b' => '01.03.2022',    // begin date
//     'ut'=> '00:00',         // begin time
//     's' => '1m',             // step size
//     'n' => 30,              // data count
//     'p' => '2',             // planet
//     'd' => '5',             // distance to planet
// ];
// $swetest->query($opt)->execute();
// $output = $swetest->response()["output"];
// foreach ($output as $line) {
//     $cols = explode(';', $line);
//     foreach($cols as &$c) {
//         $c = trim($c);
//     }
//     $cols[1] = str_replace('UT', 'GMT', $cols[1]);
//     $t = new DateTime($cols[1]);
//     $cols[1] = $t->format('Y-m-d\TH:i:s');
//     print_r(join("\t", $cols));
//     print_r("\n");
// }

class TimeFrame extends Swetest
{
    protected $begin_aspect, $end; // starting and ending datetime
    protected $resolutions = ['day' => '1', 'hour' => '60m', 'minute' => '1m', 'second' => '1s'];

    function __construct($begin_aspect, $end, $debug = false)
    {
        $this->args = [
            'geopos' => '51.50722,0.1275,0',    // location
            'head',                  // no headers
            'g' => '\;',             // separator ;
        ];
        $this->begin = is_string($begin_aspect) ? new DateTime($begin_aspect) : $begin_aspect;
        $this->end = is_string($end) ? new DateTime($end) : $end;
        $diff = $this->begin->diff($this->end);
        $diff_seconds = $diff->days*24*60*60 + $diff->h*60*60 + $diff->i*60 + $diff->s;
        $debug and print_r($diff);
        if ($diff_seconds >= 28*24*60*60) {
            // monthly timeframe
            // daily steps
            $this->args['s'] = $this->resolutions['day'];
            $this->args['n'] = $diff->days + 1;
        } else if ($diff_seconds >= 24*60*60) {
            // daily timeframe
            // hourly steps
            $this->args['s'] = $this->resolutions['hour'];
            $this->args['n'] = $diff->d * 24 + $diff->h + 1;
        } else if ($diff_seconds >= 60*60) {
            // hourly timeframe
            // minutely steps
            $this->args['s'] = $this->resolutions['minute'];
            $this->args['n'] = $diff->h * 60 + $diff->i;
        } else {
            // minute timeframe
            // secondly steps
            $this->args['s'] = $this->resolutions['second'];
            $this->args['n'] = $diff_seconds;
        }
        $this->args['b'] = $this->begin->format('d.m.Y');
        $this->args['ut'] = $this->begin->format('H:i');
        $this->path = '/usr/local/bin/';
    }
    public function findAspect($p, $d, $orb = 0.0, $resolution = '1s')
    {
        $this->args = array_merge([
            'p' => $p,
            'd' => $d,
            'f' => 'pTls',  // format: timedate, longitude, speed
        ], $this->args);

        $this->query($this->args);
        $this->execute();
        $list = $this->format();

        // $start = [];
        // $end = [];
        $findings = [];
        for ($i = 0; $i < count($list) - 2; $i++) {
            $angle_current = $list[$i]['latitude'];
            $angle_next = $list[$i+1]['latitude'];
            if ( $angle_current <= $orb && $angle_next > $orb 
            || $angle_current >= $orb && $angle_next < $orb ) {
                array_push($findings, [$list[$i], $list[$i+1]]);
            }
        }
        if ($findings) do {
            while ($this->args['s'] != $resolution) {
                $tf = new TimeFrame(pos($findings)[0]['time'], pos($findings)[1]['time']);
                $res = $tf->findAspect($p, $d, $orb, $resolution);
                return $res;
            }
        } while (next($findings));
        return $findings;
    }
    private function format() {
        $angle_col = stripos($this->args['f'], 'l');
        $time_col = stripos($this->args['f'], 'T');
        $list = [];

        foreach ($this->response()["output"] as $row) {
            $cols = explode(';', $row);
            foreach ($cols as &$c) {
                $c = trim($c);
            }
            $cols['latitude'] = floatval($cols[$angle_col]);
            $cols['time'] = str_replace('UT', 'UTC', $cols[$time_col]);
            $cols['time'] = new DateTime($cols['time']);
            array_push($list, $cols);
        }
        return $list;
    }
    public function getQueryParameters($which = null)
    {
        return $which ? $this->args[$which] : $this->args;
    }
}

// $begin_aspect = '04.03.2022 04:45';
// $end   = '31.05.2023 13:45';
// $tf    = new Timeframe($begin_aspect, $end);
// $tf->findAspect(2, 5);
// get response (status + output)


// or just get status
// print_r($swetest->getStatus());

// or just get output
// print_r($swetest->getOutput());

// get last query
// print_r($swetest->getLastQuery());
