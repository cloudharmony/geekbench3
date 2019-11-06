CREATE EXTERNAL TABLE IF NOT EXISTS [your database].geekbench_3 (
  benchmark_version VARCHAR(8),
  claim_url VARCHAR(255),
  collectd_rrd VARCHAR(255),
  floating_point_multicore_rate FLOAT,
  floating_point_multicore_score INT,
  floating_point_score INT,
  integer_multicore_rate FLOAT,
  integer_multicore_score INT,
  integer_score INT,
  iteration TINYINT,
  memory_multicore_rate FLOAT,
  memory_multicore_score INT,
  memory_score INT,
  meta_burst TINYINT,
  meta_compute_service VARCHAR(32),
  meta_compute_service_id VARCHAR(24),
  meta_cpu VARCHAR(32),
  meta_cpu_cache VARCHAR(32),
  meta_cpu_cores TINYINT,
  meta_cpu_speed FLOAT,
  meta_hostname VARCHAR(64),
  meta_instance_id VARCHAR(96),
  meta_memory VARCHAR(32),
  meta_memory_gb SMALLINT,
  meta_memory_mb INT,
  meta_os_info VARCHAR(32),
  meta_provider VARCHAR(32),
  meta_provider_id VARCHAR(24),
  meta_region VARCHAR(32),
  meta_resource_id VARCHAR(32),
  meta_run_id VARCHAR(32),
  meta_storage_config VARCHAR(32),
  meta_test_id VARCHAR(64),
  multicore_rate FLOAT,
  multicore_score INT,
  option_iterations TINYINT,
  option_workers TINYINT,
  results_html VARCHAR(255),
  results_json VARCHAR(255),
  results_text VARCHAR(255),
  results_url VARCHAR(255),
  score INT,
  test_started TIMESTAMP,
  test_stopped TIMESTAMP
)
ROW FORMAT DELIMITED
  FIELDS TERMINATED BY ','
  ESCAPED BY '\\'
  LINES TERMINATED BY '\n'
LOCATION 's3://[your S3 bucket]/path/to/csv/files/'
TBLPROPERTIES (
  'skip.header.line.count'='1'
);