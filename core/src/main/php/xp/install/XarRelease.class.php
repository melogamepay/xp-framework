<?php
  namespace xp\install;

  use \webservices\rest\RestClient;
  use \webservices\rest\RestRequest;
  use \util\cmd\Console;
  use \io\streams\StreamTransfer;
  use \io\Folder;
  use \io\File;

  /**
   * XAR release origin - uses the REST webservices @ builds.planet-xp.net
   *
   * Example payload:
   * <pre>
   * [
   *   vendor => "xp-forge"
   *   module => "mustache"
   *   version => [
   *     number => "1.0.0"
   *     series => "1.0"
   *   ]
   *   published => "2013-04-09T03:08:58+02:00"
   *   files => [
   *     0 => [
   *       name => "xp-mustache-1.0.0.xar"
   *       size => 29575
   *       sha1 => "9aeb89ae22b1ef87df3034cafc85683f7c56b020"
   *     ]
   *     1 => [
   *       name => "xp-mustache-test-1.0.0.xar"
   *       size => 22436
   *       sha1 => "ebe38e1594387fa752d103df5530b929fcc945e6"
   *     ]
   *   ]
   * ]
   * </pre>
   */
  class XarRelease extends \lang\Object implements Origin {
    private $client;
    private $release;

    /**
     * Creates a new instance
     *
     * @param string $vendor
     * @param string $module
     * @param string $branch
     */
    public function __construct($vendor, $module, $branch) {
      $this->client= new RestClient('http://builds.planet-xp.net/');
      $this->release= new RestRequest(sprintf(
        '%s/%s/%s',
        $vendor,
        $module,
        $branch
      ));
    }

    /**
     * Fetches this origin into a given target folder
     *
     * @param  io.Folder $target
     */
    public function fetchInto(Folder $target) {
      $r= $this->client->execute($this->release);
      if (200 !== $r->status()) {
        throw new \lang\IllegalArgumentException($r->message().': '.$this->resource->toString());
      }
      $release= $r->data();
      Console::writeLine('Release ', $release['version']['number'], ' published ', $release['published']);

      // Download files
      $pth= create(new File($target, 'class.pth'))->getOutputStream();
      foreach ($release['files'] as $file) {
        $d= $this->client->execute(new RestRequest($this->release->getResource().'/'.$file['name']));
        $f= new File($target, $file['name']);
        Console::writeLine('>> ', $file['name'], ' -> ', $f);

        $tran= new StreamTransfer($d->stream(), $f->getOutputStream());
        $tran->transferAll();
        $tran->close();

        $pth->write($file['name']."\n");
      }
      $pth->close();
    }
  }
?>