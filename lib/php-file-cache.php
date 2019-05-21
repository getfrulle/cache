<?php
class PhpFileCache {
  // Construct
  public function __construct($options) {
    $this->defaults();
    $this->options = array_merge($this->defaults, $options);
  }

  // Defaults
  public function defaults() {
    $this->defaults = [
      'path' => __DIR__ . '/cache',
      'extension' => 'cache',
      'expires' => strtotime('+1 days'),
    ];
  }

  // Public write
  public function write($id, $call) {
    $this->data = $call();
    $this->setFullpath($id);
    $this->createFolders();
    $this->setSerialized();
    return file_put_contents($this->fullpath, $this->serialized);
  }

  // Public read
  public function read($id) {
    if($this->has($id)) {
      $serialized = file_get_contents($this->fullpath);
      return unserialize($serialized);
    }
  }

  // Public getOrSet
  public function getOrSet($id, $call) {
    if(!$this->has($id)) {
      $this->write($id, $call);
    }
    return $this->read($id);
  }

  // Public has
  public function has($id) {
    $this->setFullpath($id);
    if(file_exists($this->fullpath)) return true;
  }

  // Public hasExpires
  public function hasExpired($id) {
    $this->setFullpath($id);
    return $this->pathExpired($this->fullpath);
  }

  // Public delete
  public function delete($id) {
    if(!$this->has($id)) return;
    return unlink($this->fullpath);
  }

  // Public filetime
  public function filetime($id) {
    if($this->has($id)) {
      return filemtime($this->fullpath);
    }
  }

  // Public flushExpired
  public function flushExpired() {
    $this->flush();
  }

  // Public flushAll
  public function flushAll() {
    $this->flush(true);
  }

  private function flush($all = false) {
    $items = $this->getItems();

    foreach($items as $item) {
      $path = $item->getRealPath();

      if ($item->isDir()) {
        if(count(scandir($path))-2 === 0) {
          rmdir($path);
        }
      } else {
        if(!$this->matchExtension($path)) continue;
        if(!$all) {
          if(!$this->pathExpired($path)) continue;
        }
        unlink($path);
      }
    }
  }

  private function getItems() {
    return new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator( $this->options['path'],
      RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
  }

  private function pathExpired($path) {
    return (filemtime($path) > $this->options['expires']);
  }

  private function matchExtension($path) {
    if(pathinfo($path, PATHINFO_EXTENSION) == $this->options['extension'])
      return true;
  }

  private function setSerialized() {
    $this->serialized = serialize($this->data);
  }

  private function createFolders() {
    $folderpath = dirname($this->fullpath);

    if(!file_exists($folderpath)) {
      return mkdir($folderpath, 0777, true);
    }
    return true;
  }

  private function fixSlashes($path) {
    return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
  }

  private function setFullpath($id) {
    $this->id = $id;

    $fullpath = sprintf('%s/%s.%s',
      $this->options['path'],
      $this->id,
      $this->options['extension']
    );

    $this->fullpath = $this->fixSlashes($fullpath);
  }
}