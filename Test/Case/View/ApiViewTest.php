<?php
App::uses('ApiView', 'Api.View');
App::uses('Controller', 'Controller');

/**
 * TestApiView
 */
class TestApiView extends ApiView {

/**
 * paths
 *
 * Visibility wrapper
 */
	public function paths($plugin = null, $cached = true) {
		return $this->_paths($plugin, $cached);
	}

/**
 * getViewFileName
 *
 * Visibility wrapper
 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

}

/**
 * ApiViewPostsController
 */
class ApiViewPostsController extends Controller {

/**
 * Helpers to be used
 *
 * @var array
 */
	public $helpers = array('Api.JsonFormat');

/**
 * name property
 *
 * @var string 'Posts'
 */
	public $name = 'Posts';

/**
 * uses property
 *
 * @var mixed null
 */
	public $uses = null;

}

/**
 * ApiViewTest
 */
class ApiViewTest extends CakeTestCase {

/**
 * setUp
 */
	public function setUp() {
		parent::setUp();

		$request = $this->getMock('CakeRequest');
		$this->Controller = new Controller($request);
		$this->PostsController = new ApiViewPostsController($request);
		$this->PostsController->viewPath = 'Posts';
		$this->View = new TestApiView($this->PostsController);

		$this->testAppPath = dirname(dirname(__DIR__)) . '/test_app/';
		App::build(array(
			'Plugin' => array($this->testAppPath . 'Plugin/'),
			'View' => array($this->testAppPath . 'View/')
		), App::RESET);
		App::objects('plugins', null, false);

		CakePlugin::load('Api',	array('bootstrap' => true, 'routes' => true));
		Configure::write('debug', 2);
	}

/**
 * testPaths
 */
	public function testPaths() {
		$paths = $this->View->paths();
		foreach ($paths as &$path) {
			$path = str_replace(
				array($this->testAppPath, APP, CAKE),
				array('APP/', 'APP/', 'CAKE/'),
				$path
			);
		}

		$expected = array(
			'APP/View/',
			'CAKE/View/',
			'CAKE/Console/Templates/skel/View/',
			'APP/Plugin/Api/View/'
		);
		$this->assertSame($expected, $paths);
	}

/**
 * testPathsPlugin
 */
	public function testPathsPlugin() {
		CakePlugin::load('ApiTestPlugin');
		$paths = $this->View->paths('ApiTestPlugin');
		foreach ($paths as &$path) {
			$path = str_replace(
				array($this->testAppPath, APP, CAKE),
				array('APP/', 'APP/', 'CAKE/'),
				$path
			);
		}

		$expected = array(
			'APP/View/Plugin/ApiTestPlugin/',
			'APP/Plugin/ApiTestPlugin/View/',
			'APP/View/',
			'CAKE/View/',
			'CAKE/Console/Templates/skel/View/',
			'APP/Plugin/Api/View/'
		);
		$this->assertSame($expected, $paths);
	}

/**
 * testGetViewFileName
 */
	public function testGetViewFileName() {
		$path = $this->View->getViewFileName('index');

		$path = str_replace(
			array($this->testAppPath, APP, CAKE),
			array('APP/', 'APP/', 'CAKE/'),
			$path
		);

		$expected = 'APP/View/Posts/json/index.ctp';
		$this->assertSame($expected, $path);
	}

/**
 * testGetViewFileNameSubfolder
 */
	public function testGetViewFileNameSubfolder() {
		$path = $this->View->getViewFileName('sub/view');

		$path = str_replace(
			array($this->testAppPath, APP, CAKE),
			array('APP/', 'APP/', 'CAKE/'),
			$path
		);

		$expected = 'APP/View/Posts/json/sub/view.ctp';
		$this->assertSame($expected, $path);
	}

/**
 * testGetViewFileNameDefault
 *
 * If neither success nor data has been defined - expect at missing view exception
 *
 * @expectedException MissingViewException
 */
	public function testGetViewFileNameDefault() {
		$path = $this->View->getViewFileName('no-view-file');
	}

/**
 * testGetViewFileNameDefaultSuccess
 */
	public function testGetViewFileNameDefaultSuccess() {
		$this->View->viewVars['success'] = true;
		$path = $this->View->getViewFileName('no-view-file');

		$path = str_replace(
			array($this->testAppPath, APP, CAKE),
			array('APP/', 'APP/', 'CAKE/'),
			$path
		);

		$expected = 'APP/Plugin/Api/View/json/fallback_template.ctp';
		$this->assertSame($expected, $path);
	}

/**
 * testGetViewFileNameDefaultData
 */
	public function testGetViewFileNameDefaultData() {
		$this->View->viewVars['data'] = true;
		$path = $this->View->getViewFileName('no-view-file');

		$path = str_replace(
			array($this->testAppPath, APP, CAKE),
			array('APP/', 'APP/', 'CAKE/'),
			$path
		);

		$expected = 'APP/Plugin/Api/View/json/fallback_template.ctp';
		$this->assertSame($expected, $path);
	}

	public function testGetViewFileNameDefaultSubfolder() {
		$this->View->viewVars['data'] = true;
		$path = $this->View->getViewFileName('sub/no-view');

		$path = str_replace(
			array($this->testAppPath, APP, CAKE),
			array('APP/', 'APP/', 'CAKE/'),
			$path
		);

		$expected = 'APP/Plugin/Api/View/json/fallback_template.ctp';
		$this->assertSame($expected, $path);
	}

/**
 * testRenderIndex
 *
 * Fake rendering a paginated index - verify response format
 */
	public function testRenderIndex() {
		$this->View->viewVars = array(
			'success' => true,
			'data' => array(
				array('one'),
				array('two'),
				array('three')
			),
			'pagination' => array(
				'pageCount' => 1,
				'current' => 1,
				'count' => 1,
				'prev' => 'Some url',
				'next' => 'Some other url'
			),
			'allowJsonp' => true
		);

		$debug = Configure::read('debug');
		Configure::write('debug', 0);
		$return = $this->View->render('index', 'Api.json/default');
		Configure::write('debug', $debug);

		$return = json_decode($return, true);
		$this->assertTrue((bool)$return, "Response was not valid json");
		$expected = array(
			'success' => true,
			'data' => array(
				array('one'),
				array('two'),
				array('three')
			),
			'pagination' => array(
				'pageCount' => 1,
				'current' => 1,
				'count' => 1,
				'prev' => 'Some url',
				'next' => 'Some other url'
			)
		);
		$this->assertSame($expected, $return);
	}

/**
 * testRenderIndexNoPagination
 *
 * Fake rendering a paginated index - verify that having passed "showPaginationLinks" as false works
 */
	public function testRenderIndexNoPagination() {
		$this->View->viewVars = array(
			'success' => true,
			'data' => array(
				array('one'),
				array('two'),
				array('three')
			),
			'pagination' => array(
				'page' => 1,
				'pageCount' => 1,
				'current' => 1,
				'count' => 1,
				'prev' => 'Some url',
				'next' => 'Some other url'
			),
			'allowJsonp' => true,
			'showPaginationLinks' => false
		);

		$this->View->Paginator = $this->getMock('Helper', array('defaultModel', 'hasPrev', 'hasNext'));
		$this->View->Paginator->expects($this->any())
			->method('defaultModel')
			->will($this->returnValue('Name'));

		$this->View->Paginator->request = new Object();
		$this->View->Paginator->request->paging['Name'] = $this->View->viewVars['pagination'];

		$debug = Configure::read('debug');
		Configure::write('debug', 0);
		$return = $this->View->render('index', 'Api.json/default');
		Configure::write('debug', $debug);

		$return = json_decode($return, true);
		$this->assertTrue((bool)$return, "Response was not valid json");
		$expected = array(
			'success' => true,
			'data' => array(
				array('one'),
				array('two'),
				array('three')
			),
			'pagination' => array(
				'pageCount' => 1,
				'current' => 1,
				'count' => 1
			)
		);
		$this->assertSame($expected, $return);
	}

/**
 * Testing real paths, is not exposed in exception trace
 *
 * @return void
 */
	public function testStripRealPaths() {
		Configure::write('ResponseObject', $this->Controller->request);
		$this->View->viewVars = array(
			'success' => false,
			'error' => new Exception('Test Exception'),
			'_serialize' => array(), //@todo empty
			'allowJsonp' => true
		);

		$debug = Configure::read('debug');
		Configure::write('debug', 2);
		$this->View->render('exception', 'Api.json/error');

		$this->assertNotContains(WWW_ROOT, $this->View->viewVars['data']['trace']);
		$this->assertNotContains(CAKE, $this->View->viewVars['data']['trace']);
		$this->assertNotContains(APP, $this->View->viewVars['data']['trace']);
		$this->assertNotContains(ROOT, $this->View->viewVars['data']['trace']);
		$this->assertNotContains(WEBROOT_DIR, $this->View->viewVars['data']['trace']);

		$this->assertContains('CAKE/', $this->View->viewVars['data']['trace']);
		$this->assertContains('APP/', $this->View->viewVars['data']['trace']);

		Configure::write('debug', $debug);
	}

}
