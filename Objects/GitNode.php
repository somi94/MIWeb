<?php
namespace MIWeb\Git\Objects;

class GitNode implements GitTreeInterface {
    const NODE_TYPE_BLOB = 'blob';
    const NODE_TYPE_TREE = 'tree';
    const NODE_TYPE_LINK = 'commit';
	
    /**
     * permissions
     *
     * @var string
     */
    protected $permissions;
	
    /**
     * type
     *
     * @var string
     */
    protected $type;
	
    /**
     * sha
     *
     * @var string
     */
    protected $sha;
	
    /**
     * size
     *
     * @var string
     */
    protected $size;
	
    /**
     * name
     *
     * @var string
     */
    protected $name;
	
    /**
     * path
     *
     * @var string
     */
    protected $path;
	
    /**
     * Class constructor
     *
     * @param string                  $permissions node permissions
     * @param string                  $type        node type
     * @param string                  $sha         node sha
     * @param string                  $size        node size in bytes
     * @param string                  $name        node name
     * @param string                  $path        node path
     */
    public function __construct($permissions, $type, $sha, $size, $name, $path) {
        $this->permissions = $permissions;
        $this->type        = $type;
        $this->sha         = $sha;
        $this->size        = $size;
        $this->name        = $name;
        $this->path        = $path;
    }
	
    /**
     * toString magic method
     *
     * @return string
     */
    public function __toString() {
        return (string)$this->name;
    }
	
    /**
     * Mime Type getter
     *
     * @param string $basePath the base path of the repository
     *
     * @return string
     */
    public function getMimeType($basePath) {
        return mime_content_type($basePath . DIRECTORY_SEPARATOR . $this->path);
    }
	
    /**
     * get extension if it's a blob
     *
     * @return string|null
     */
    public function getExtension() {
        $pos = strrpos($this->name, '.');
        if($pos === false) {
            return null;
        }
		
		return substr($this->name, $pos + 1);
    }
	
    /**
     * Full path getter
     *
     * @return string
     */
    public function getFullPath() {
        return rtrim(
            !$this->path ? $this->name : $this->path . DIRECTORY_SEPARATOR . $this->name,
            DIRECTORY_SEPARATOR
        );
    }
	
    /*
     * gets the last commit in this object
     *
     * @return Commit
     *
    public function getLastCommit()
    {
        $log = $this->repository->getLog('HEAD', $this->getFullPath(), 1);
        return $log[0];
    }
    /**
     * rev-parse command - often used to return a commit tag.
     *
     * @param array         $options the options to apply to rev-parse
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @return array
     *
    public function revParse(Array $options = array()) {
        $c = RevParseCommand::getInstance()->revParse($this, $options);
        $caller = $this->repository->getCaller();
        $caller->execute($c);
        return array_map('trim', $caller->getOutputLines(true));
    }*/
	
    /**
     * permissions getter
     *
     * @return string
     */
    public function getPermissions() {
        return $this->permissions;
    }
	
    /**
     * sha getter
     *
     * @return string
     */
    public function getSha() {
        return $this->sha;
    }
	
    /**
     * type getter
     *
     * @return string
     */
    public function getNodeType() {
        return $this->type;
    }
	
    /**
     * name getter
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }
	
    /**
     * path getter
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }
	
    /**
     * size getter
     *
     * @return string
     */
    public function getSize() {
        return $this->size;
    }
	
    /**
     * whether the node is a tree
     *
     * @return bool
     */
    public function isTree() {
        return $this->type == self::NODE_TYPE_TREE;
    }
	
    /**
     * whether the node is a link
     *
     * @return bool
     */
    public function isLink() {
        return $this->type == self::NODE_TYPE_LINK;
    }
	
    /**
     * whether the node is a blob
     *
     * @return bool
     */
    public function isBlob() {
        return $this->type == self::NODE_TYPE_BLOB;
    }
	
	/**
     * create a Object from a single outputLine of the git ls-tree command
     *
     * @param \GitElephant\Repository $repository repository instance
     * @param string                  $outputLine output from ls-tree command
     *
     * @see LsTreeCommand::tree
     * @return NodeObject
     */
    public static function createFromOutputLine($outputLine) {
        $slices = static::getLineSlices($outputLine);
		
        $fullPath = $slices['fullPath'];
        if(!($pos = mb_strrpos($fullPath, '/'))) {
            // repository root
            $path = '';
            $name = $fullPath;
        } else {
            $path = substr($fullPath, 0, $pos);
            $name = substr($fullPath, $pos + 1);
        }
		
        return new static(
            $repository,
            $slices['permissions'],
            $slices['type'],
            $slices['sha'],
            $slices['size'],
            $name,
            $path
        );
    }
	
    /**
     * Take a line and turn it in slices
     *
     * @param string $line a single line output from the git binary
     *
     * @return array
     */
    public static function getLineSlices($line) {
        preg_match('/^(\d+) (\w+) ([a-z0-9]+) +(\d+|-)\t(.*)$/', $line, $matches);
        $permissions = $matches[1];
        $type        = null;
        switch ($matches[2]) {
            case GitNode::TYPE_TREE:
                $type = GitNode::NODE_TYPE_TREE;
                break;
            case GitNode::NODE_TYPE_BLOB:
                $type = GitNode::NODE_TYPE_BLOB;
                break;
            case GitNode::NODE_TYPE_LINK:
                $type = GitNode::NODE_TYPE_LINK;
                break;
        }
        $sha      = $matches[3];
        $size     = $matches[4];
        $fullPath = $matches[5];
        return array(
            'permissions' => $permissions,
            'type'        => $type,
            'sha'         => $sha,
            'size'        => $size,
            'fullPath'    => $fullPath
        );
    }
}
