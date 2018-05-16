<?php
namespace MIWeb\Git\Agents;

interface GitAgentInterface {
	/**
	 * execute the given command
	 * 
	 * @param string $cmd the command to execute
	 * @param string $workdir the path to execute on
	 * @return GitAgent the agent instance (returned for chaining)
	 */
	public function execute($cmd, $workdir = null);
	
	/**
	 * get output
	 * @param bool $clean strip empty lines
	 * @return GitAgent the agent instance (returned for chaining)
	 */
	public function getOutput($clean = false);
}
