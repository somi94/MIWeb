<?php
namespace MIWeb\Git;

class GitAgent implements GitAgentInterface {
    /**
     * GitBinary instance
     *
     * @var GitBinary
     */
    protected $binary;
	
	/**
	 * Default workdir
	 *
	 * @var string
	 */
	protected $workdir;
	
	/**
	 * last commands output
	 *
	 * @var array
	 */
	protected $commandOutput;
	
	/**
	 * last commands exit status
	 *
	 * @var int
	 */
	protected $commandStatus;
	 
    /**
     * Class constructor
     *
     * @param GitBinary $binary the binary to use
	 * @param string
     */
    public function __construct($workdir = null,$binary = null) {
		if(!$binary) {
			$binary = new GitBinary();
		}
        $this->binary = $binary;
		$this->workdir = $workdir;
    }
	
	/**
	 * @return string the GitBinary used by the agent
	 */
	public function getBinary() {
		return $this->binary;
	}
	
	/**
	 * @param string $binary the GitBinary used by the agent
	 */
	public function setBinary($binary) {
		$this->binary = $binary;
	}
	
	/**
	 * @return string the agents current default workdir
	 */
	public function getWorkdir() {
		return $this->workdir;
	}
	
	/**
	 * @param string $workdir the agents default workdir
	 */
	public function setWorkdir($workdir) {
		$this->workdir = $workdir;
	}
	
	/**
	 * execute the given command
	 * 
	 * @param string $cmd the command to execute
	 * @param string $workdir the path to execute on
	 * @return GitAgent the agent instance (returned for chaining)
	 */
	public function execute($cmd, $workdir = null) {
		if(!$workdir && !$this->workdir) {
			throw new \Exception("No workdir given to execute command on. Set the default workdir of the GitAgent instance or pass it to it's execute method.");
		} else if(!$workdir) {
			$workdir = $this->workdir;
		}
		
		if(!is_dir($workdir)) {
			throw new \Exception("Invalid GitAgent workdir '$workdir'.");
		}
		
		$exec = 'GIT_DIR=' . $workdir . ' ' . $this->binary->getPath() . ' ' . $cmd;
		
		unset($this->commandOutput);
		unset($this->commandStatus);
		exec($exec, $this->commandOutput, $this->commandStatus);
		
		return $this;
	}
	
	/**
	 * get output
	 * @param bool $clean strip empty lines
	 * @return GitAgent the agent instance (returned for chaining)
	 */
	public function getOutput($clean = false) {
		if($clean) {
			$out = [];
			foreach($this->commandOutput as $line) {
				if(trim($line)) {
					$out[] = $line;
				}
			}
			return $out;
		}
		
		return $this->commandOutput;
	}
	
	/**
	 * get output
	 * @param bool $clean strip empty lines
	 * @return GitAgent the agent instance (returned for chaining)
	 */
	public function getStatus() {
		return $this->commandStatus;
	}
}
