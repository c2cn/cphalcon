<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2013 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

class ModelsRelationsMagicTest extends PHPUnit_Framework_TestCase
{

	public function __construct()
	{
		spl_autoload_register(array($this, 'modelsAutoloader'));
	}

	public function __destruct()
	{
		spl_autoload_unregister(array($this, 'modelsAutoloader'));
	}

	public function modelsAutoloader($className)
	{
		$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
		if (file_exists('unit-tests/models/' . $className . '.php')) {
			require 'unit-tests/models/' . $className . '.php';
		}
	}

	protected function _getDI()
	{

		Phalcon\DI::reset();

		$di = new Phalcon\DI();

		$di->set('modelsManager', function(){
			return new Phalcon\Mvc\Model\Manager();
		});

		$di->set('modelsMetadata', function(){
			return new Phalcon\Mvc\Model\Metadata\Memory();
		});

		return $di;
	}

	public function testModelsMysql()
	{

		$di = $this->_getDI();

		require 'unit-tests/config.db.php';
		if (empty($configMysql)) {
			$this->markTestSkipped('Test skipped');
			return;
		}

		$connection = new Phalcon\Db\Adapter\Pdo\Mysql($configMysql);

		$di->set('db', $connection);

		$this->_executeQueryRelated();
		$this->_executeSaveRelatedBelongsTo($connection);
	}

	/*public function testModelsPostgresql()
	{

		$di = $this->_getDI();

		$di->set('db', function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Postgresql($configPostgresql);
		});

		$this->_executeQueryRelated();
		$this->_executeSaveRelatedBelongsTo($connection);
	}

	public function testModelsSqlite()
	{

		$di = $this->_getDI();

		$di->set('db', function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Sqlite($configSqlite);
		});

		$this->_executeQueryRelated();
		$this->_executeSaveRelatedBelongsTo($connection);
	}*/

	public function _executeQueryRelated()
	{

		//Belongs to
		$album = AlbumORama\Albums::findFirst();
		$this->assertEquals(get_class($album), 'AlbumORama\Albums');

		$artist = $album->artist;
		$this->assertEquals(get_class($artist), 'AlbumORama\Artists');

		$albums = $artist->albums;
		$this->assertEquals(get_class($albums), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($albums), 2);
		$this->assertEquals(get_class($albums[0]), 'AlbumORama\Albums');

		$songs = $album->songs;
		$this->assertEquals(get_class($songs), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($songs), 7);
		$this->assertEquals(get_class($songs[0]), 'AlbumORama\Songs');

		$originalAlbum = $album->artist->albums[0];
		$this->assertEquals($originalAlbum->id, $album->id);
	}

	public function _executeSaveRelatedBelongsTo($connection)
	{
		$artist = new AlbumORama\Artists();

		$album = new AlbumORama\Albums();
		$album->artist = $artist;

		//Due to not null fields on both models the album/artist aren't saved
		$this->assertFalse($album->save());
		$this->assertFalse((bool) $connection->isUnderTransaction());

		//The artists must no be saved
		$this->assertEquals($artist->getDirtyState(), Phalcon\Mvc\Model::DIRTY_STATE_TRANSIENT);

		//The messages produced are generated by the artist model
		$messages = $album->getMessages();
		$this->assertEquals($messages[0]->getMessage(), 'name is required');
		$this->assertEquals(get_class($messages[0]->getModel()), 'AlbumORama\Artists');

		//Fix the artist problem and try to save again
		$artist->name = 'Van She';

		//Due to not null fields on album model the whole
		$this->assertFalse($album->save());
		$this->assertFalse((bool) $connection->isUnderTransaction());

		//The artist model was saved correctly but album not
		$this->assertEquals($artist->getDirtyState(), Phalcon\Mvc\Model::DIRTY_STATE_PERSISTENT);
		$this->assertEquals($album->getDirtyState(), Phalcon\Mvc\Model::DIRTY_STATE_TRANSIENT);

		$messages = $album->getMessages();
		$this->assertEquals($messages[0]->getMessage(), 'name is required');
		$this->assertEquals(gettype($messages[0]->getModel()), 'NULL');

		//Fix the album problem and try to save again
		$album->name = 'Idea of Happiness';

		//Saving OK
		$this->assertTrue($album->save());
		$this->assertFalse((bool) $connection->isUnderTransaction());

		//Both messages must be saved correctly
		$this->assertEquals($artist->getDirtyState(), Phalcon\Mvc\Model::DIRTY_STATE_PERSISTENT);
		$this->assertEquals($album->getDirtyState(), Phalcon\Mvc\Model::DIRTY_STATE_PERSISTENT);
	}

}
