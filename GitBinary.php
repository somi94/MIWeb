<?php
namespace MIWeb\Git;

class GitBinary {
    /**
     * the path to the binary
     *
     * @var string $path
     */
    protected $path;
	
    /**
     * Class constructor
     *
     * @param null $path the physical path to the git binary
     */
    public function __construct($path = null) {
        if(!$path) {
			//no git path given: try to detect it
			$path = $this->findGitPath();
        }
		if(!$path) {
			throw new \Exception("No git path given and couldn't autodetect it. Pass the path of your git binary to the GitBinary constructor.");
		}
		
        $this->setPath($path);
    }
	
	public function getPath() {
		return $this->path;
	}
	
	protected function findGitPath() {
		//TODO: find solution for windows
		try {
			return exec('which git');
		} catch(\Throwable $thrown) {
		}
		return null;
	}
}
