<?php
namespace MIWeb\Git\Objects;

interface GitTreeInterface {
    /**
     * get the unique sha for the tree object
     *
     * @abstract
     */
    public function getSha();
	
    /**
     * toString magic method, should return the sha of the tree object
     *
     * @abstract
     */
    public function __toString();
}
