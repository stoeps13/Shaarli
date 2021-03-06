<?php
namespace Shaarli\Api\Controllers;

use PHPUnit\Framework\TestCase;
use Shaarli\Bookmark\BookmarkFileService;
use Shaarli\Config\ConfigManager;
use Shaarli\History;
use Slim\Container;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class InfoTest
 *
 * Test REST API controller Info.
 *
 * @package Api\Controllers
 */
class InfoTest extends TestCase
{
    /**
     * @var string datastore to test write operations
     */
    protected static $testDatastore = 'sandbox/datastore.php';

    /**
     * @var ConfigManager instance
     */
    protected $conf;

    /**
     * @var \ReferenceLinkDB instance.
     */
    protected $refDB = null;

    /**
     * @var Container instance.
     */
    protected $container;

    /**
     * @var Info controller instance.
     */
    protected $controller;

    /**
     * Before every test, instantiate a new Api with its config, plugins and bookmarks.
     */
    protected function setUp(): void
    {
        $this->conf = new ConfigManager('tests/utils/config/configJson');
        $this->conf->set('resource.datastore', self::$testDatastore);
        $this->refDB = new \ReferenceLinkDB();
        $this->refDB->write(self::$testDatastore);

        $history = new History('sandbox/history.php');

        $this->container = new Container();
        $this->container['conf'] = $this->conf;
        $this->container['db'] = new BookmarkFileService($this->conf, $history, true);
        $this->container['history'] = null;

        $this->controller = new Info($this->container);
    }

    /**
     * After every test, remove the test datastore.
     */
    protected function tearDown(): void
    {
        @unlink(self::$testDatastore);
    }

    /**
     * Test /info service.
     */
    public function testGetInfo()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
        ]);
        $request = Request::createFromEnvironment($env);

        $response = $this->controller->getInfo($request, new Response());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);

        $this->assertEquals(\ReferenceLinkDB::$NB_LINKS_TOTAL, $data['global_counter']);
        $this->assertEquals(2, $data['private_counter']);
        $this->assertEquals('Shaarli', $data['settings']['title']);
        $this->assertEquals('?', $data['settings']['header_link']);
        $this->assertEquals('Europe/Paris', $data['settings']['timezone']);
        $this->assertEquals(ConfigManager::$DEFAULT_PLUGINS, $data['settings']['enabled_plugins']);
        $this->assertEquals(true, $data['settings']['default_private_links']);

        $title = 'My bookmarks';
        $headerLink = 'http://shaarli.tld';
        $timezone = 'Europe/Paris';
        $enabledPlugins = array('foo', 'bar');
        $defaultPrivateLinks = true;
        $this->conf->set('general.title', $title);
        $this->conf->set('general.header_link', $headerLink);
        $this->conf->set('general.timezone', $timezone);
        $this->conf->set('general.enabled_plugins', $enabledPlugins);
        $this->conf->set('privacy.default_private_links', $defaultPrivateLinks);

        $response = $this->controller->getInfo($request, new Response());
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);

        $this->assertEquals(\ReferenceLinkDB::$NB_LINKS_TOTAL, $data['global_counter']);
        $this->assertEquals(2, $data['private_counter']);
        $this->assertEquals($title, $data['settings']['title']);
        $this->assertEquals($headerLink, $data['settings']['header_link']);
        $this->assertEquals($timezone, $data['settings']['timezone']);
        $this->assertEquals($enabledPlugins, $data['settings']['enabled_plugins']);
        $this->assertEquals($defaultPrivateLinks, $data['settings']['default_private_links']);
    }
}
