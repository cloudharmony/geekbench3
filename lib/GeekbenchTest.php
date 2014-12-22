<?php
// Copyright 2014 CloudHarmony Inc.
// 
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
// 
//     http://www.apache.org/licenses/LICENSE-2.0
// 
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.


/**
 * Used to manage GEEKBENCH testing
 */
require_once(dirname(__FILE__) . '/util.php');
ini_set('memory_limit', '16m');
date_default_timezone_set('UTC');

class GeekbenchTest {
  
  /**
   * name of the file where serializes options should be written to for given 
   * test iteration
   */
  const GEEKBENCH_TEST_OPTIONS_FILE_NAME = '.options';
  
  /**
   * name of the file geekbench stdout is written to
   */
  const GEEKBENCH_TEST_FILE_NAME = 'geekbench.out';
  
  /**
   * name of the file geekbench stderror is written to
   */
  const GEEKBENCH_TEST_ERR_FILE = 'geekbench.err';
  
  /**
   * name of the file geekbench status is written to
   */
  const GEEKBENCH_TEST_EXIT_FILE = 'geekbench.status';
  
  /**
   * optional results directory object was instantiated for
   */
  private $dir;
  
  /**
   * run options
   */
  private $options;
  
  
  /**
   * constructor
   * @param string $dir optional results directory object is being instantiated
   * for. If set, runtime parameters will be pulled from the .options file. Do
   * not set when running a test
   */
  public function GeekbenchTest($dir=NULL) {
    $this->dir = $dir;
  }
  
  /**
   * writes test results and finalizes testing
   * @return boolean
   */
  private function endTest() {
    $ended = FALSE;
    $dir = $this->options['output'];
    
    // add test stop time
    $this->options['test_stopped'] = date('Y-m-d H:i:s');
    
    // serialize options
    $ofile = sprintf('%s/%s', $dir, self::GEEKBENCH_TEST_OPTIONS_FILE_NAME);
    if (is_dir($dir) && is_writable($dir)) {
      $fp = fopen($ofile, 'w');
      fwrite($fp, serialize($this->options));
      fclose($fp);
      $ended = TRUE;
    }
    
    return $ended;
  }
  
  /**
   * returns results from testing as a hash of key/value pairs
   * @return array
   */
  public function getResults() {
    $results = NULL;
    if (isset($this->dir) && is_dir($this->dir) && file_exists($ofile = sprintf('%s/%s', $this->dir, self::GEEKBENCH_TEST_FILE_NAME))) {
      foreach($this->getRunOptions() as $key => $val) {
        if (preg_match('/^meta_/', $key) || preg_match('/^test_/', $key)) $results[$key] = $val;
      }
      if ($handle = popen(sprintf('%s/parse.php %s', dirname(__FILE__), $ofile), 'r')) {
        while(!feof($handle) && ($line = fgets($handle))) {
          if (preg_match('/^([a-z][^=]+)=(.*)$/', $line, $m)) $results[$m[1]] = $m[2];
        }
        fclose($handle);
      }
    }
    return $results;
  }
  
  /**
   * returns run options represents as a hash
   * @return array
   */
  public function getRunOptions() {
    if (!isset($this->options)) {
      if ($this->dir) $this->options = self::getSerializedOptions($this->dir);
      else {
        // default run argument values
        $sysInfo = get_sys_info();
        $defaults = array(
          'collectd_rrd_dir' => '/var/lib/collectd/rrd',
          'meta_compute_service' => 'Not Specified',
          'meta_cpu' => $sysInfo['cpu'],
          'meta_instance_id' => 'Not Specified',
          'meta_memory' => $sysInfo['memory_gb'] > 0 ? $sysInfo['memory_gb'] . ' GB' : $sysInfo['memory_mb'] . ' MB',
          'meta_os' => $sysInfo['os_info'],
          'meta_provider' => 'Not Specified',
          'meta_storage_config' => 'Not Specified',
          'output' => trim(shell_exec('pwd'))
        );
        $opts = array(
          'collectd_rrd',
          'collectd_rrd_dir:',
          'geekbench_dir:',
          'meta_compute_service:',
          'meta_compute_service_id:',
          'meta_cpu:',
          'meta_instance_id:',
          'meta_memory:',
          'meta_os:',
          'meta_provider:',
          'meta_provider_id:',
          'meta_region:',
          'meta_resource_id:',
          'meta_run_id:',
          'meta_storage_config:',
          'meta_test_id:',
          'output:',
          'upload',
          'v' => 'verbose',
          'x32'
        );
        $this->options = parse_args($opts); 
        foreach($defaults as $key => $val) {
          if (!isset($this->options[$key])) $this->options[$key] = $val;
        } 
      }
    }
    return $this->options;
  }
  
  /**
   * returns options from the serialized file where they are written when a 
   * test completes
   * @param string $dir the directory where results were written to
   * @return array
   */
  public static function getSerializedOptions($dir) {
    return unserialize(file_get_contents(sprintf('%s/%s', $dir, self::GEEKBENCH_TEST_OPTIONS_FILE_NAME)));
  }
  
  /**
   * initiates stream scaling testing. returns TRUE on success, FALSE otherwise
   * @return boolean
   */
  public function test() {
    $rrdStarted = isset($this->options['collectd_rrd']) ? ch_collectd_rrd_start($this->options['collectd_rrd_dir'], isset($this->options['verbose'])) : FALSE;
    $success = FALSE;
    $this->getRunOptions();
    $this->options['test_started'] = date('Y-m-d H:i:s');
    $ofile = sprintf('%s/%s', $this->options['output'], self::GEEKBENCH_TEST_FILE_NAME);
    $efile = sprintf('%s/%s', $this->options['output'], self::GEEKBENCH_TEST_ERR_FILE);
    $xfile = sprintf('%s/%s', $this->options['output'], self::GEEKBENCH_TEST_EXIT_FILE);
    $cmd = sprintf('cd %s;%s/geekbench_x86_%d --export-html geekbench.html --export-json geekbench.json --export-text geekbench.txt --%supload --benchmark | tee %s 2>%s;echo $? > %s', $this->options['output'], $this->options['geekbench_dir'], isset($this->options['x32']) ? 32 : 64, isset($this->options['upload']) ? '' : 'no-', $ofile, $efile, $xfile);
    
    passthru($cmd);
    $ecode = trim(file_get_contents($xfile));
    $ecode = strlen($ecode) && is_numeric($ecode) ? $ecode*1 : NULL;
    unlink($xfile);
    if (file_exists($efile) && filesize($efile)) {
      print_msg(sprintf('Unable run Geekbench using command %s - exit code %d', $cmd, $ecode), isset($this->options['verbose']), __FILE__, __LINE__, TRUE);
      print_msg(trim(file_get_contents($efile)), isset($this->options['verbose']), __FILE__, __LINE__, TRUE);
      unlink($efile);
    }
    else if (file_exists($ofile) && $ecode === 0) {
      $success = TRUE;
      print_msg(sprintf('Geekbench test finished - results written to %s', $ofile), isset($this->options['verbose']), __FILE__, __LINE__);
      if ($rrdStarted) ch_collectd_rrd_stop($this->options['collectd_rrd_dir'], $this->options['output'], isset($this->options['verbose']));
      $this->endTest();
    }
    else print_msg(sprintf('Geekbench failed to run - exit code %d', $ecode), isset($this->options['verbose']), __FILE__, __LINE__, TRUE);
    if (file_exists($efile)) unlink($efile);
    
    return $success;
  }
  
  /**
   * validate run options. returns an array populated with error messages 
   * indexed by the argument name. If options are valid, the array returned
   * will be empty
   * @return array
   */
  public function validateRunOptions() {
    $this->getRunOptions();
    $validate = array(
      'output' => array('write' => TRUE),
    );
    $validated = validate_options($this->options, $validate);
    if (!is_array($validated)) $validated = array();
    
    // look up directory hierarchy for Geekbench directory
    if (!isset($this->options['geekbench_dir'])) {
      $version = ($ini = get_benchmark_ini()) ? $ini['meta-version'] : NULL;
      print_msg(sprintf('Geekbench directory not set - looking up directory hierarchy'), isset($this->options['verbose']), __FILE__, __LINE__);
      $dirs = array($this->options['output']);
      if (($pwd = trim(shell_exec('pwd'))) != $this->options['output']) $dirs[] = $pwd;
      foreach($dirs as $dir) {
        while($dir != dirname($dir)) {
          if ((is_dir($udir = sprintf('%s/dist/Geekbench-%s-Linux', $dir, $version)) || is_dir($udir = sprintf('%s/Geekbench-%s-Linux', $dir, $version))) && file_exists(sprintf('%s/geekbench_x86_64', $udir))) {
            print_msg(sprintf('Geekbench found in directory %s', $dir), isset($this->options['verbose']), __FILE__, __LINE__);
            $this->options['geekbench_dir'] = $udir;
            break;
          }
          else print_msg(sprintf('Geekbench (%s) NOT found in directory %s', $udir, $dir), isset($this->options['verbose']), __FILE__, __LINE__);
          $dir = dirname($dir);
        }
        if (isset($this->options['geekbench_dir'])) break;
      }
    }
    
    // check if Geekbench is valid and has been compiled
    if (isset($this->options['geekbench_dir']) && is_dir($this->options['geekbench_dir'])) {
      if (!file_exists($run = sprintf('%s/geekbench_x86_64', $this->options['geekbench_dir'])) || !is_executable($run)) $validated['geekbench_dir'] = '--geekbench_dir ' . $this->options['geekbench_dir'] . ' does not contain geekbench_x86_64';
      else {
        // check if Geekbench is registered
        $sysinfo = shell_exec(sprintf('%s/geekbench_x86_%d --sysinfo 2>/dev/null', $this->options['geekbench_dir'], isset($this->options['x32']) ? 32 : 64));
        if (strpos($sysinfo, 'Processor')) print_msg(sprintf('Geekbench directory %s is valid and registered', $this->options['geekbench_dir']), isset($this->options['verbose']), __FILE__, __LINE__);
        else $validated['geekbench_dir'] = sprintf('Geekbench has not been registered [ecode=%d; user=%s]. Register using %s/geekbench_x86_64 -r [email] [registration key]', $ecode, trim(shell_exec('whoami')), $this->options['geekbench_dir']);
      }
    }
    else $validated['geekbench_dir'] = isset($this->options['geekbench_dir']) ? '--geekbench_dir ' . $this->options['geekbench_dir'] . ' is not valid' : '--geekbench_dir is required';
    
    // validate collectd rrd options
    if (isset($this->options['collectd_rrd'])) {
      if (!ch_check_sudo()) $validated['collectd_rrd'] = 'sudo privilege is required to use this option';
      else if (!is_dir($this->options['collectd_rrd_dir'])) $validated['collectd_rrd_dir'] = sprintf('The directory %s does not exist', $this->options['collectd_rrd_dir']);
      else if ((shell_exec('ps aux | grep collectd | wc -l')*1 < 2)) $validated['collectd_rrd'] = 'collectd is not running';
      else if ((shell_exec(sprintf('find %s -maxdepth 1 -type d 2>/dev/null | wc -l', $this->options['collectd_rrd_dir']))*1 < 2)) $validated['collectd_rrd_dir'] = sprintf('The directory %s is empty', $this->options['collectd_rrd_dir']);
    }
    
    return $validated;
  }
  
}
?>
