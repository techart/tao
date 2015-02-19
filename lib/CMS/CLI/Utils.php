<?php

Core::load('CMS.CLI');

class CMS_CLI_Utils extends CMS_CLI_Handler implements Core_ModuleInterface
{
	public function component_assets_dump()
	{
		$component_name = reset($this->args);
		$result = CMS::component($component_name)->assets_dump_all(true);
		print("Dump Files:\n");
		print("===================\n");
		foreach ($result as $from => $to) {
			print("$from => $to\n");
		}
		print("===================\n");
	}

	public function tao_clear_cache()
	{
		$key = reset($this->args);
		if ($key) {
			print("Clear cache by key {$key} ... \n");
			WS::env()->cache->delete($key);
			print("Done\n");
		} else {
			print("Clear all cache ...\n");
			WS::env()->cache->flush();
			print("Done\n");
		}
	}

	public function tao_cache()
	{
		if (count($this->args) < 2) {
			print("no args\n");
			return;
		}
		$command = array_shift($this->args);
		$args = $this->args;
		$result = var_export(Core::invoke(array(WS::env()->cache, $command), $args), true);
		print("$result\n");
	}

	public function sqlc()
	{
		$dsn = WS::env()->db->default->dsn;
		$cmd = $this->sql_command($dsn);
		$rc = !(bool) $this->popen($cmd);
		return $rc;
	}

	public function sql_dump()
	{
		$dsn = WS::env()->db->default->dsn;
		$cmd = $this->sql_command($dsn, true);
		$rc = !(bool) $this->popen($cmd);
		return $rc;
	}

	public function sql_query()
	{
		$query = reset($this->args);
		if (!$query) {
			return;
		}
		$dsn = WS::env()->db->default->dsn;
		$cmd = $this->sql_command($dsn);
		switch($dsn->type) {
			case "mysql":
				$cmd .= " -e \"$query\"";
				break;
			case "pgsql":
				$cmd .= " -c \"$query\"";
				break;
		}
		$rc = !(bool) $this->popen($cmd);
		return $rc;
	}

	protected function sql_command($dsn, $dump = false)
	{
		switch($dsn->type) {
			case "mysql":
				$command = $dump ? "mysqldump " : "mysql ";
				$creds = " -p{$dsn->password} -u{$dsn->user}";
				$port = isset($dsn->port) ? $dsn->port : 3306;
				$extra = " -h{$dsn->host} -P{$port} {$dsn->database}";
				return $command . $creds . $extra;
			case "pgsql":
				$util = $dump ? "pg_dump" : "psql -q";
				$port = isset($dsn->port) ? $dsn->port : 5432;
				$command = "PGPASSWORD=\"{$dsn->password}\" $util ";
				$creds = " --username=\"{$dsn->user}\" --host=\"{$dsn->host}\" --dbname=\"{$dsn->database}\" --port=\"{$port}\"";
				$extra = $dump ? "" : "";
				return $command . $creds . $extra;
		}
	}

	protected function popen($cmd)
	{
		$process = proc_open($cmd, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes);
		$proc_status = proc_get_status($process);
		$exit_code = proc_close($process);
		return ($proc_status["running"] ? $exit_code : $proc_status["exitcode"] );
	}
}