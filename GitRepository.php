<?php
namespace MIWeb\Git;

class GitRepository {
	/**
	 * @var GitAgent the agent to use
	 */
	protected $agent;
	
	/**
	 * @var string the repository name
	 */
	protected $name;
	
	/**
	 * @param string $path the path to the repository
	 * @param GitBinary $binary the GitBinary to use
	 * @param string $name
	 */
	public function __construct($path, $binary = null, $name = null) {
		$this->agent = new GitAgent($path, $binary);
		$this->name = $name;
	}
	
	/**
	 * @param GitAgent $agent the agent to use
	 */
	public function setAgent($agent) {
		$this->agent = $agent;
	}
	
	/**
	 * @return GitAgent $agent the current agent
	 */
	public function getAgent() {
		return $this->agent;
	}
	
	/**
	 * @return string path to the repository
	 */
	public function getPath() {
		return $this->agent->getWorkdir();
	}
	
	/**
	 * @param string $path the path to the repository
	 */
	public function setPath($path) {
		$this->agent->setWorkdir($path);
	}
	
	
	
	/**
	 * initialize an empty repository
	 */
	public function init() {
		$this->agent->execute('init');
		
		return $this;
	}
	
	/**
	 * add a remote address to the repository
	 * @param string $name name of the remote
	 * @param string $url the remote url
	 */
	public function addRemote($name, $url) {
		$this->agent->execute('remote add "' . $name . '" "' . $url . '"');
		
		return $this;
	}
	
	/**
	 * create a new repository from the given remote url
	 * @param string $name name of the remote
	 * @param string $url the remote url
	 */
	public function cloneFrom($url) {
		$this->agent->execute('clone "' . $url . '" "' . $this->getPath() . '"');
		
		return $this;
	}
	
	/**
     * Checkout a reference (branch, commit, ...)
     * This function change the state of the repository on the filesystem
     *
     * @param string|TreeishInterface $ref    the reference to checkout
     * @param bool                    $create like -b on the command line
     *
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     * @return Repository
     */
    public function checkout($ref, $createBranch = false) {
        if($create && !$this->getBranch($ref)) {
            $this->createBranch($ref);
        }
        $this->agent->execute('checkout "' . $ref . '"');
		
        return $this;
    }
	
	public function checkoutAllRemoteBranches() {
		$actualBranch = $this->getCurrentBranch();
        $actualBranches = $this->getBranches(true, false);
        $allBranches = $this->getBranches(true, true);
        $realBranches = array_filter(
            $allBranches,
            function ($branch) use ($actualBranches) {
                return !in_array($branch, $actualBranches)
                && preg_match('/^remotes(.+)$/', $branch)
                && !preg_match('/^(.+)(HEAD)(.*?)$/', $branch);
            }
        );
        foreach ($realBranches as $realBranch) {
            $this->checkout(str_replace(sprintf('remotes/%s/', $remote), '', $realBranch));
        }
		
        $this->checkout($actualBranch);
		
        return $this;
	}
	
    /**
     * Create a new branch
     *
     * @param string 		$name       the new branch name
     * @param string|null   $startPoint the reference to create the branch from
	 * @param bool 			$checkout 	checkout after creation?
     *
     * @throws \RuntimeException
     * @return GitRepository
     */
    public function createBranch($name, $startPoint = null, $checkout = true) {
        $this->agent->execute('branch "' . $name . '"' . ($startPoint ? ' "' . $startPoint . '"' : ''));
		if($checkout) {
			$this->checkout($name);
		}
		
        return $this;
    }
	
    /**
     * delete a branch
     *
     * @param string 		$name       the branch to delete
     *
     * @throws \RuntimeException
     * @return GitRepository
     */
    public function deleteBranch($name) {
        $this->agent->execute('branch -d "' . $name . '"');
		
        return $this;
    }
	
    /**
     * Return the actually checked out branch
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return GitBranch
     */
    public function getCurrentBranch() {
		throw new \Exception("getCurrentBranch() not implemented yet.");
        /*$filtered = array_filter(
            $this->getBranches(),
            function (Branch $branch) {
                return $branch->getCurrent();
            }
        );
        sort($filtered);
        return $filtered[0];*/
    }
	
    /**
     * Retrieve a Branch object by a branch name
     *
     * @param string $name The branch name
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return Branch
     */
    public function getBranch($name) {
        /** @var Branch $branch */
        foreach ($this->getBranches() as $branch) {
            if ($branch->getName() == $name) {
                return $branch;
            }
        }
		
        return null;
    }
	
    /**
     * An array of Branch objects
     *
     * @param bool $namesOnly return an array of branch names as a string
     * @param bool $all       lists also remote branches
     *
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getBranches($namesOnly = false, $all = false) {
        $branches = array();
        if($namesOnly) {
            $outputLines = $this->agent->execute(
				'branch --no-color --no-abbrev' . ($all ? ' -a' : '')
            )->getOutput(true);
            
			$branches = array_map(
                function ($v) {
                    return ltrim($v, '* ');
                },
                $outputLines
            );
        } else {
            $outputLines = $this->caller->execute(
				'branch -v --no-color --no-abbrev' . ($all ? ' -a' : '')
            )->getOutputLines(true);
            foreach ($outputLines as $branchLine) {
                $branches[] = Branch::createFromOutputLine($branchLine);
            }
        }
		
        return $branches;
    }
	
	/**
     * create a repository from a remote git url, or a local filesystem
     * and save it in the defined path or a tmp folder
     *
     * @param string|GitRepository 	$git            	the git remote url, or the filesystem path
     * @param null              	$repositoryPath 	path
     * @param GitBinary         	$binary         	binary
     * @param null              	$name           	repository name
     *
     * @return GitRepository
     */
    public static function createFromRemote($git, $repositoryPath = null, GitBinary $binary = null, $name = null) {
        if(!$repositoryPath) {
            $tempDir = realpath(sys_get_temp_dir());
            $repositoryPath = sprintf('%s%s%s', $tempDir, DIRECTORY_SEPARATOR, sha1(uniqid()));
            
			mkdir($repositoryPath,0777,true);
        }
		
        $repository = new {get_called_class()}($repositoryPath, $binary, $name);
        if($git instanceof GitRepository) {
            $git = $git->getPath();
        }
		
        $repository->cloneFrom($git);
        $repository->checkoutAllRemoteBranches();
		
        return $repository;
    }
	
    /**
     * Factory method
     *
     * @param string        $repositoryPath the path of the git repository
     * @param GitBinary 	$binary         the GitBinary instance that calls the commands
     * @param string        $name           a repository name
     *
     * @return GitRepository
     */
    public static function open($repositoryPath, GitBinary $binary = null, $name = null) {
        return new {get_called_class()}($repositoryPath, $binary, $name);
    }
}
