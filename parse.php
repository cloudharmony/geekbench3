#!/usr/bin/php -q
<?php
/**
 * looks for geekbench.json in the current iteration run directory. If it 
 * exists, this file is parsed and used as the basis for generating result 
 * metrics. Exit code is 0 on success, 1 on failure
 */
$status = 1;
if (isset($argv[1]) && file_exists($argv[1]) && file_exists($json = dirname($argv[1]) . '/geekbench.json')) {
  $results_url = NULL;
  $claim_url = NULL;
  // results and claim URLs
  if (preg_match_all('/http([\S]+)\s/msU', file_get_contents($argv[1]), $m)) {
    foreach($m[0] as $url) {
      if (preg_match('/claim/', $url)) $claim_url = trim($url);
      else if (preg_match('/geekbench3/', $url)) $results_url = trim($url);
    }
  }
  if ($results = json_decode(file_get_contents($json), TRUE)) {
    $status = 0;
    if ($claim_url) printf("claim_url=%s\n", $claim_url);
    if ($results_url) printf("results_url=%s\n", $results_url);
    foreach($results as $key => $val) {
      if (in_array($key, array('version', 'score', 'multicore_score', 'multicore_rate', 'runtime'))) printf("%s=%s\n", $key, trim($val));
      else if ($key == 'options' && is_array($val)) {
        foreach($val as $option => $value) printf("option_%s=%s\n", $option, trim($value));
      }
      else if ($key == 'metrics' && is_array($val)) {
        foreach($val as $property) if (isset($property['name']) && isset($property['value']) && trim($property['value'])) printf("meta_%s=%s\n", str_replace('-', '_', str_replace(' ', '_', trim(strtolower($property['name'])))), trim($property['value']));
      }
      else if ($key == 'sections' && is_array($val)) {
        foreach($val as $section) {
          if ($prefix = str_replace('-', '_', str_replace(' ', '_', trim(strtolower($section['name']))))) {
            foreach($section as $skey => $sval) {
              if (in_array($skey, array('score', 'multicore_score', 'multicore_rate', 'graph_width')) && is_numeric($sval) && $sval > 0) printf("%s_%s=%s\n", $prefix, $skey, $sval);
              else if ($skey == 'workloads' && is_array($sval)) {
                foreach($sval as $workload) {
                  if (isset($workload['name']) && ($wname = str_replace('-', '_', str_replace(' ', '_', trim(strtolower($workload['name'])))))) {
                    foreach($workload as $wkey => $wval) {
                      if (in_array($wkey, array('score', 'multicore_score', 'multicore_rate'))) printf("%s_%s_%s=%s\n", $prefix, $wname, $wkey, trim($wval));
                      else if ($wkey == 'results' && is_array($wval)) {
                        foreach($wval as $result) {
                          if (is_array($result) && isset($result['threads'])) {
                            $thread_label = $result['threads'] > 1 ? 'multithread' : 'singlethread';
                            foreach($result as $rkey => $rval) {
                              if (in_array($rkey, array('score', 'runtime', 'runtime_max', 'runtime_mean', 'runtime_median', 'runtime_stddev', 'threads', 'workload_rate', 'work', 'rate_string'))) printf("%s_%s_%s_%s=%s\n", $prefix, $wname, $thread_label, $rkey, trim($rval));
                            }   
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
exit($status);
?>