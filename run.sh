#!/bin/bash
# Copyright 2014 CloudHarmony Inc.
# 
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.


if [ "$1" == "-h" ] || [ "$1" == "--help" ] ; then
  cat << EOF
Usage: run.sh [options]

This repository contains the setup and runtime configurations for the Geekbench 
version 3 benchmark. Geekbench is a commercial benchmark developed by Primate 
Labs. More information about Geekbench is available on their website:

http://www.primatelabs.com/geekbench/

This benchmark requires a registered version of Geekbench. To register, you 
must first purchase a license key, then download the Geekbench software, and 
finally enter your registration key - e.g.

dist/Geekbench-3.1.6-Linux/geekbench_x86_64 -r [email] [registration key]


TESTING PARAMETERS
Test behavior is fixed, but you may specify the following optional meta 
attributes and installation attributes. The meta attributes will be included in 
the results (see save.sh). Geekbench should be installed and registered before 
running this benchmark. Review the 'geekbench_dir' parameter comments below
for instructions.


--collectd_rrd              If set, collectd rrd stats will be captured from 
                            --collectd_rrd_dir. To do so, when testing starts,
                            existing directories in --collectd_rrd_dir will 
                            be renamed to .bak, and upon test completion 
                            any directories not ending in .bak will be zipped
                            and saved along with other test artifacts (as 
                            collectd-rrd.zip). User MUST have sudo privileges
                            to use this option
                            
--collectd_rrd_dir          Location where collectd rrd files are stored - 
                            default is /var/lib/collectd/rrd

--geekbench_dir             Directory where Geekbench is installed. If not 
                            specified, the benchmark run script will look up 
                            the directory tree from both pwd and --output for 
                            presence of a 'dist/Geekbench-3.1.6-Linux' or 
                            'Geekbench-3.1.6-Linux' directory with an 
                            executable 'geekbench_x86_64' executable in it. The 
                            test harness will check if Geekbench has been 
                            registered and generate an error if it has not

--meta_compute_service      The name of the compute service this test pertains
                            to. May also be specified using the environment 
                            variable bm_compute_service
                            
--meta_compute_service_id   The id of the compute service this test pertains
                            to. Added to saved results. May also be specified 
                            using the environment variable bm_compute_service_id
                            
--meta_cpu                  CPU descriptor - if not specified, it will be set 
                            using the 'model name' attribute in /proc/cpuinfo
                            
--meta_instance_id          The compute service instance type this test pertains 
                            to (e.g. c3.xlarge). May also be specified using 
                            the environment variable bm_instance_id
                            
--meta_memory               Memory descriptor - if not specified, the system
                            memory size will be used
                            
--meta_os                   Operating system descriptor - if not specified, 
                            it will be taken from the first line of /etc/issue
                            
--meta_provider             The name of the cloud provider this test pertains
                            to. May also be specified using the environment 
                            variable bm_provider
                            
--meta_provider_id          The id of the cloud provider this test pertains
                            to. May also be specified using the environment 
                            variable bm_provider_id
                            
--meta_region               The compute service region this test pertains to. 
                            May also be specified using the environment 
                            variable bm_region
                            
--meta_resource_id          An optional benchmark resource identifiers. May 
                            also be specified using the environment variable 
                            bm_resource_id
                            
--meta_run_id               An optional benchmark run identifiers. May also be 
                            specified using the environment variable bm_run_id
                            
--meta_storage_config       Storage configuration descriptor. May also be 
                            specified using the environment variable 
                            bm_storage_config
                            
--meta_test_id              Identifier for the test. May also be specified 
                            using the environment variable bm_test_id
                            
--output                    The output directory to use for writing test data 
                            (results html, json and text). If not specified, 
                            the current working directory will be used
                            
--upload                    Upload results upon completion to the public 
                            Geekbench results browser (includes system 
                            information)
                            
--verbose                   Show verbose output

--x32                       Run in 32 bit mode (geekbench_x86_32) - defaults 
                            to 64 bit (geekbench_x86_64)
                            
                            
DEPENDENCIES
This benchmark has the following dependencies:

  php-cli     Test automation scripts (/usr/bin/php)

  zip         Used to compress test artifacts


USAGE
# run 1 test iteration with some metadata
./run.sh --meta_compute_service_id aws:ec2 --meta_instance_id c3.xlarge --meta_region us-east-1 --meta_test_id aws-0914

# run with Geekbench installed in /usr/local/Geekbench and upload results to public Geekbench browser
./run.sh --geekbench_dir /usr/local/Geekbench --upload

# run 10 test iterations using a specific output directory
for i in {1..10}; do mkdir -p ~/geekbench-testing/$i; ./run.sh --output ~/geekbench-testing/$i; done


# save.sh saves results to CSV, MySQL, PostgreSQL, BigQuery or via HTTP 
# callback. It can also save artifacts (HTML, JSON and text results) to S3, 
# Azure Blob Storage or Google Cloud Storage

# save results to CSV files
./save.sh

# save results from 5 iterations text example above
./save.sh ~/geekbench-testing

# save results to a PostgreSQL database
./save --db postgresql --db_user dbuser --db_pswd dbpass --db_host db.mydomain.com --db_name benchmarks

# save results to BigQuery and artifact (TRIAD gnuplot PNG image) to S3
./save --db bigquery --db_name benchmark_dataset --store s3 --store_key THISIH5TPISAEZIJFAKE --store_secret thisNoat1VCITCGggisOaJl3pxKmGu2HMKxxfake --store_container benchmarks1234


EXIT CODES:
  0 test successful
  1 test failed

EOF
  exit
elif [ -f "/usr/bin/php" ] && [ -f "/usr/bin/perl" ]; then
  $( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/lib/run.php $@
  exit $?
else
  echo "Error: missing dependency php-cli (/usr/bin/php), perl (/usr/bin/perl)"
  exit 1
fi
