<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Tests;

use Mongator\Query\Query;
use MongoDB\BSON\ObjectID;

class QueryTest extends TestCase
{
    protected $identityMap;
    protected $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityMap = $this->mongator->getRepository('Model\Article')->getIdentityMap();
        $this->query = new \Model\ArticleQuery($this->mongator->getRepository('Model\Article'));
    }

    public function testConstructor()
    {
        $query = new \Model\CategoryQuery($repository = $this->mongator->getRepository('Model\Category'));
        $this->assertSame($repository, $query->getRepository());
        $hash = $query->getHash();
        $this->assertInternalType('string', $hash);
        $this->assertSame($hash, $query->getHash());
    }

    public function testFieldsCache()
    {
        $cache = $this->query->getFullCache();
        $this->assertFalse(isset($cache['fields']));

        $this->cache->set($this->query->getHash(), array('fields' => $fields = array('title' => 1, 'content' => 1)));

        $cache = $this->query->getFullCache();
        $this->assertSame($fields, $cache['fields']);

        $this->cache->remove($this->query->getHash());
        $cache = $this->query->getFullCache();
        $this->assertFalse(isset($cache['fields']));
    }

    public function testCriteria()
    {
        $query = $this->query;
        $this->assertSame(array(), $query->getCriteria());

        $criteria = array('is_active' => true);
        $this->assertSame($query, $query->criteria($criteria));
        $this->assertSame($criteria, $query->getCriteria());

        $criteria = array('title' => 'foo', 'content' => 'bar');
        $query->criteria($criteria);
        $this->assertSame($criteria, $query->getCriteria());
    }

    public function testMergeCriteria()
    {
        $query = $this->query;

        $criteria1 = array('is_active' => true);
        $this->assertSame($query, $query->mergeCriteria($criteria1));
        $this->assertSame($criteria1, $query->getCriteria());

        $criteria2 = array('author' => new ObjectID());
        $query->mergeCriteria($criteria2);
        $this->assertSame(array('is_active' => true, 'author' => $criteria2['author']), $query->getCriteria());

        $criteria3 = array('is_active' => false);
        $query->mergeCriteria($criteria3);
        $this->assertSame(array('is_active' => false, 'author' => $criteria2['author']), $query->getCriteria());
    }

    public function testFields()
    {
        $query = $this->query;
        $this->assertSame(array(), $query->getFields());

        $fields = array('title' => 1, 'content' => 1);
        $this->assertSame($query, $query->fields($fields));
        $this->assertSame($fields, $query->getFields());

        $fields = array('_id' => 1);
        $query->fields($fields);
        $this->assertSame($fields, $query->getFields());
    }

    public function testReferences()
    {

        $query = $this->query;
        $this->assertSame(array(), $query->getReferences());

        $references = array('user', 'author');
        $this->assertSame($query, $query->references($references));
        $this->assertSame($references, $query->getReferences());

        $include = array('post');
        $query->references($references);
        $this->assertSame($references, $query->getReferences());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotArrayOrNull
     */
    public function testReferencesNotArrayOrNull($value)
    {
        $this->query->references($value);
    }

    public function testSort()
    {
        $query = $this->query;
        $this->assertNull($query->getSort());

        $sort = array('is_active' => 1);
        $this->assertSame($query, $query->sort($sort));
        $this->assertSame($sort, $query->getSort());

        $sort = array('date' => -1, 'title' => 1);
        $query->sort($sort);
        $this->assertSame($sort, $query->getSort());

        $query->sort(null);
        $this->assertNull($query->getSort());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotArrayOrNull
     */
    public function testSortNotArrayOrNull($value)
    {
        $this->query->sort($value);
    }

    public function testLimit()
    {
        $query = $this->query;
        $this->assertNull($query->getLimit());

        $this->assertSame($query, $query->limit(10));
        $this->assertSame(10, $query->getLimit());

        $query->limit('20');
        $this->assertSame(20, $query->getLimit());

        $query->limit(null);
        $this->assertNull($query->getLimit());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotValidIntOrNull
     */
    public function testLimitNotValidIntOrNull($value)
    {
        $this->query->limit($value);
    }

    public function testSkip()
    {
        $query = $this->query;
        $this->assertNull($query->getSkip());

        $this->assertSame($query, $query->skip(15));
        $this->assertSame(15, $query->getSkip());

        $query->skip('40');
        $this->assertSame(40, $query->getSkip());

        $query->skip(null);
        $this->assertNull($query->getSkip());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotValidIntOrNull
     */
    public function testSkipNotValidIntOrNull($value)
    {
        $this->query->skip($value);
    }

    public function testBatchSize()
    {
        $query = $this->query;
        $this->assertNull($query->getBatchSize());

        $this->assertSame($query, $query->batchSize(15));
        $this->assertSame(15, $query->getBatchSize());

        $query->batchSize('40');
        $this->assertSame(40, $query->getBatchSize());

        $query->batchSize(null);
        $this->assertNull($query->getBatchSize());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotValidIntOrNull
     */
    public function testBatchSizeNotValidIntOrNull($value)
    {
        $this->query->batchSize($value);
    }

    public function testHint()
    {
        $query = $this->query;
        $this->assertNull($query->getHint());

        $hint = array('username' => 1);
        $this->assertSame($query, $query->hint($hint));
        $this->assertSame($hint, $query->getHint());

        $hint = array('username' => 1, 'date' => 1);
        $query->hint($hint);
        $this->assertSame($hint, $query->getHint());

        $query->hint(null);
        $this->assertNull($query->getHint());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotArrayOrNull
     */
    public function testHintNotArrayOrNull($value)
    {
        $this->query->hint($value);
    }

    public function testSlaveOkay()
    {
        $query = $this->query;
        $this->assertNull($query->getSlaveOkay());

        $this->assertSame($query, $query->slaveOkay(true));
        $this->assertTrue($query->getSlaveOkay());

        $this->assertSame($query, $query->slaveOkay(false));
        $this->assertFalse($query->getSlaveOkay());

        $query->slaveOkay(null);
        $this->assertNull($query->getSlaveOkay());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotBoolean
     */
    public function testSlaveOkayNotBoolean($value)
    {
        $this->query->slaveOkay($value);
    }

    public function testSnapshot()
    {
        $query = $this->query;
        $this->assertFalse($query->getSnapshot());

        $this->assertSame($query, $query->snapshot(true));
        $this->assertTrue($query->getSnapshot());

        $query->snapshot(false);
        $this->assertFalse($query->getSnapshot());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotBoolean
     */
    public function testSnapshotNotBoolean($value)
    {
        $this->query->snapshot($value);
    }

    public function testTimeout()
    {
        $query = $this->query;
        $this->assertNull($query->getTimeout());

        $this->assertSame($query, $query->timeout(15));
        $this->assertSame(15, $query->getTimeout());

        $query->timeout('40');
        $this->assertSame(40, $query->getTimeout());

        $query->timeout(null);
        $this->assertNull($query->getTimeout());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider      providerNotValidIntOrNull
     */
    public function testTimeoutNotValidIntOrNull($value)
    {
        $this->query->timeout($value);
    }

    public function testText()
    {
        $query = $this->query;
        $this->assertNull($query->getText());

        $expected = array(
            'search' => 'foo',
            'requiredScore' => null,
            'language' => null
        );

        $this->assertSame($query, $query->text('foo'));
        $this->assertSame($expected, $query->getText());

        $expected = array(
            'search' => 'bar',
            'requiredScore' => null,
            'language' => 'english'
        );

        $this->assertSame($query, $query->text('bar', null, 'english'));
        $this->assertSame($expected, $query->getText());

        $expected = array(
            'search' => 'qux',
            'requiredScore' => 100,
            'language' => null
        );

        $this->assertSame($query, $query->text('qux', 100, null));
        $this->assertSame($expected, $query->getText());

        $query->text(null);
        $this->assertNull($query->getText());
    }

    public function testAllCache()
    {
        $baseArticles = $this->createArticles(10);

        foreach ($baseArticles as $baseArticle) {
            $this->assertFalse($this->identityMap->has($baseArticle->getId()));
        }
        $articles = $this->query->all();

        $this->getCollection('Model\Article')->remove();

        foreach ($articles  as $article) {
            $this->assertTrue(strlen($article->getTitle()) > 0 );
        }
    }

    public function testAll()
    {
        $baseArticles = $this->createArticles(10);

        foreach ($baseArticles as $baseArticle) {
            $this->assertFalse($this->identityMap->has($baseArticle->getId()));
        }
        $articles = $this->query->all();

        foreach ($articles as $article) {
            $this->assertTrue($this->identityMap->has($article->getId()));
            $this->assertSame(array($this->query->getHash()), $article->getQueryHashes());
        }

        $query = new \Model\ArticleQuery($this->mongator->getRepository('Model\Article'));
        $articles2 = $query->all();

        $this->assertCount(10, $articles2);

        foreach ($articles2 as $key => $article2) {
            $this->assertSame($article2, $articles[$key]);
            $this->assertSame(array($this->query->getHash(), $query->getHash()), $article2->getQueryHashes());
        }
    }

    public function testAllNullFields()
    {
        $articleRaw = array(
            'content' => 'bar',
            'source' => array(
                'note' => 'fooups',
                'info' => array(
                    ''
                ),
            ),
        );
        $this->mongator->getRepository('Model\Article')->getCollection()->insert($articleRaw);

        $article = $this->mongator->getRepository('Model\Article')->createQuery()->fields(array('title' => 1, 'source.name' => 1))->one();

        $this->assertNull($article->getTitle());
        $this->assertNull($article->getSource()->getName());

        $articleRaw['title'] = 'foo';
        $articleRaw['source']['name'] = 'foobar';
        $this->mongator->getRepository('Model\Article')->getCollection()->save($articleRaw);

        $this->assertNull($article->getTitle());
        $this->assertNull($article->getSource()->getName());
    }

    public function testAllReferencesOne()
    {
        $articles = array();
        for ($i = 0; $i < 9; $i++) {
            $articles[] = $this->mongator->create('Model\Article')->setTitle('Article'.$i)->save();
        }
        $authors = array();
        for ($i = 0; $i < 9; $i++) {
            $authors[] = $this->mongator->create('Model\Author')->setName('Author'.$i)->save();
        }

        $articles[1]->setAuthor($authors[1])->save();
        $articles[3]->setAuthor($authors[3])->save();
        $articles[4]->setAuthor($authors[3])->save();
        $articles[6]->setAuthor($authors[6])->save();

        $articleIdentityMap = $this->mongator->getRepository('Model\Article')->getIdentityMap();
        $authorIdentityMap = $this->mongator->getRepository('Model\Author')->getIdentityMap();

        // without reference
        $articleIdentityMap->clear();
        $authorIdentityMap->clear();

        $this->mongator->getRepository('Model\Article')->createQuery()->all();
        foreach ($articles as $article) {
            $this->assertTrue($articleIdentityMap->has($article->getId()));
        }
        foreach ($authors as $author) {
            $this->assertFalse($authorIdentityMap->has($author->getId()));
        }

        // with reference, finding all
        $articleIdentityMap->clear();
        $authorIdentityMap->clear();

        $this->mongator->getRepository('Model\Article')->createQuery()->references(array('author'))->all();
        foreach ($articles as $article) {
            $this->assertTrue($articleIdentityMap->has($article->getId()));
        }
        foreach ($authors as $i => $author) {
            if (in_array($i, array(1, 3, 6))) {
                $this->assertTrue($authorIdentityMap->has($author->getId()));
            } else {
                $this->assertFalse($authorIdentityMap->has($author->getId()));
            }
        }

        // with reference, finding some
        $articleIdentityMap->clear();
        $authorIdentityMap->clear();

        $this->mongator->getRepository('Model\Article')->createQuery(array(
            '_id' => array('$nin' => array($articles[6]->getId()))
        ))->references(array('author'))->all();
        foreach ($articles as $i => $article) {
            if (6 == $i) {
                $this->assertFalse($articleIdentityMap->has($article->getId()));
            } else {
                $this->assertTrue($articleIdentityMap->has($article->getId()));
            }
        }
        foreach ($authors as $i => $author) {
            if (in_array($i, array(1, 3))) {
                $this->assertTrue($authorIdentityMap->has($author->getId()));
            } else {
                $this->assertFalse($authorIdentityMap->has($author->getId()));
            }
        }
    }

    public function testAllReferencesMany()
    {
        $articles = array();
        for ($i = 0; $i < 9; $i++) {
            $articles[] = $this->mongator->create('Model\Article')->setTitle('Article'.$i)->save();
        }
        $categories = array();
        for ($i = 0; $i < 9; $i++) {
            $categories[] = $this->mongator->create('Model\Category')->setName('Category'.$i)->save();
        }

        $articles[1]->getCategories()->add(array($categories[1], $categories[2]));
        $articles[1]->save();
        $articles[3]->getCategories()->add(array($categories[2], $categories[3]));
        $articles[3]->save();
        $articles[5]->getCategories()->add(array($categories[5]));
        $articles[5]->save();

        $articleIdentityMap = $this->mongator->getRepository('Model\Article')->getIdentityMap();
        $categoryIdentityMap = $this->mongator->getRepository('Model\Category')->getIdentityMap();

        // without reference
        $articleIdentityMap->clear();
        $categoryIdentityMap->clear();

        $this->mongator->getRepository('Model\Article')->createQuery()->all();
        foreach ($articles as $article) {
            $this->assertTrue($articleIdentityMap->has($article->getId()));
        }
        foreach ($categories as $category) {
            $this->assertFalse($categoryIdentityMap->has($category->getId()));
        }

        // with references, finding some
        $articleIdentityMap->clear();
        $categoryIdentityMap->clear();

        $this->mongator->getRepository('Model\Article')->createQuery()->references(array('categories'))->all();
        foreach ($articles as $article) {
            $this->assertTrue($articleIdentityMap->has($article->getId()));
        }
        foreach ($categories as $i => $category) {
            if (in_array($i, array(1, 2, 3, 5))) {
                $this->assertTrue($categoryIdentityMap->has($category->getId()));
            } else {
                $this->assertFalse($categoryIdentityMap->has($category->getId()));
            }
        }

        // with references, finding some
        $articleIdentityMap->clear();
        $categoryIdentityMap->clear();

        $this->mongator->getRepository('Model\Article')->createQuery(array(
            '_id' => array('$nin' => array($articles[5]->getId())),
        ))->references(array('categories'))->all();
        foreach ($articles as $i => $article) {
            if (5 == $i) {
                $this->assertFalse($articleIdentityMap->has($article->getId()));
            } else {
                $this->assertTrue($articleIdentityMap->has($article->getId()));
            }
        }
        foreach ($categories as $i => $category) {
            if (in_array($i, array(1, 2, 3))) {
                $this->assertTrue($categoryIdentityMap->has($category->getId()));
            } else {
                $this->assertFalse($categoryIdentityMap->has($category->getId()));
            }
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAllReferencesNotExist()
    {
        $this->mongator->getRepository('Model\Article')->createQuery()->references(array('no'))->all();
    }

    public function testIterator()
    {
        $articles = $this->createArticles(10);

        foreach ($articles as $article) {
            $this->assertFalse($this->identityMap->has($article->getId()));
        }

        $array = iterator_to_array($this->query);
        $this->assertEquals(count($articles), count($array));

        $i = 0; $keys = array_keys($array);
        foreach ($articles as $article) {
            $this->assertEquals($article->getId(), $array[$keys[$i++]]->getId());
            $this->assertTrue($this->identityMap->has($article->getId()));
        }
    }

    public function testOne()
    {
        $articles = $this->createArticles(10);

        foreach ($articles as $article) {
            $this->assertFalse($this->identityMap->has($article->getId()));
        }

        $articleOne = array_shift($articles);
        $one = $this->query->one();
        $this->assertInstanceOf('Model\Article', $one);
        $this->assertEquals($articleOne->getId(), $one->getId());

        $this->assertTrue($this->identityMap->has($articleOne->getId()));
        foreach ($articles as $article) {
            $this->assertFalse($this->identityMap->has($article->getId()));
        }
    }

    public function testOneWithoutResults()
    {
        $this->assertNull($this->query->one());
    }

    public function testOneNotChangeQueryLimit()
    {
        $this->query->limit(10);
        $this->query->one();
        $this->assertSame(10, $this->query->getLimit());
    }

    public function testCount()
    {
        $articles = $this->createArticlesRaw(20);
        $this->assertSame(20, $this->query->count());
    }

    public function testCountableInterface()
    {
        $articles = $this->createArticlesRaw(5);
        $this->assertSame(5, count($this->query));
    }

    public function testCreateCursor()
    {
        $query = $this->query;

        $cursor = $query->createCursor();
        $this->assertInstanceOf('Mongator\Query\Result', $cursor);

        $articles = $this->createArticlesRaw(10);
        $results = iterator_to_array($cursor);
        foreach ($articles as $article) {
            $this->assertTrue(isset($results[$article['_id']->__toString()]));
        }
    }

    public function testCreateCursorPlaying()
    {
        $query = $this->query;

        $query
            ->criteria(array('is_active' => true))
            ->fields(array('title' => 1))
            ->sort(array('date' => -1))
            ->limit(10)
            ->skip(25)
            ->batchSize(5)
            ->hint(array('username' => 1))
            ->snapshot(true)
            ->timeout(100)
        ;

        $cursor = $query->createCursor();
        $this->assertInstanceOf('Mongator\Query\Result', $cursor);
    }

    public function testCreateResult()
    {
        $messages = $this->createMessageRaw(10);
        $this->mongator->getRepository('Model\Message')->ensureIndexes();

        $query = new \Model\MessageQuery($this->mongator->getRepository('Model\Message'));
        $query->text('author 1');

        $result = $query->createResult();
        $this->assertInstanceOf('Mongator\Query\Result', $result);
        $this->assertSame(10, count($result));

        $first = $result->current();
        $this->assertSame('Author 1', $first['author']);
        $this->assertSame('Text 1', $first['text']);
    }

    public function testCreateResultPlaying()
    {
        $messages = $this->createMessageRaw(10);
        $this->mongator->getRepository('Model\Message')->ensureIndexes();

        $query = new \Model\MessageQuery($this->mongator->getRepository('Model\Message'));
        $query
            ->text('author 1')
            ->skip(1)
            ->limit(5)
            ->fields(array('author' => 1));

        $result = $query->createResult();
        $this->assertInstanceOf('Mongator\Query\Result', $result);
        $this->assertSame(5, count($result));

        $first = $result->current();
        $this->assertSame('Author 0', $first['author']);
        $this->assertFalse(isset($first['text']));
    }

    public function testCreateResultWithRequiredScore()
    {
        $messages = $this->createMessageRaw(10);
        $this->mongator->getRepository('Model\Message')->ensureIndexes();

        $query = new \Model\MessageQuery($this->mongator->getRepository('Model\Message'));
        $query->text('author 1', 100);

        $result = $query->createResult();
        $this->assertInstanceOf('Mongator\Query\Result', $result);
        $this->assertSame(1, count($result));
    }

    public function testCreateResultNoText()
    {
        $messages = $this->createMessageRaw(10);
        $this->mongator->getRepository('Model\Message')->ensureIndexes();

        $query = new \Model\MessageQuery($this->mongator->getRepository('Model\Message'));
        $query
            ->limit(5)
            ->hint(array('author' => 1))
            ->fields(array('author' => 1));

        $result = $query->createResult();
        $this->assertFalse($result);
    }

    public function testCreateResultNonAssocFields()
    {
        $messages = $this->createMessageRaw(10);
        $this->mongator->getRepository('Model\Message')->ensureIndexes();

        $query = new \Model\MessageQuery($this->mongator->getRepository('Model\Message'));
        $query
            ->text('author 1')
            ->limit(5)
            ->fields(array('author'));

        $result = $query->createResult();

        $first = $result->current();
        $this->assertSame('Author 1', $first['author']);
        $this->assertFalse(isset($first['text']));
    }

    public function testExecuteWithCreateResult()
    {
        $messages = $this->createMessageRaw(10);
        $this->mongator->getRepository('Model\Message')->ensureIndexes();

        $query = new \Model\MessageQuery($this->mongator->getRepository('Model\Message'));
        $query
            ->text('author 1')
            ->limit(5)
            ->fields(array('author' => 1));

        $this->assertInstanceOf('Mongator\Query\Result', $query->execute());
        $this->assertSame(5, $query->count());

        foreach ($query->all() as $key => $document) {
            $this->assertSame($key, (string) $document->getId());
            $this->assertInstanceOf('Model\Message', $document);
        }
    }

    public function testExecuteWithCreateCursor()
    {
        $messages = $this->createMessageRaw(10);
        $this->mongator->getRepository('Model\Message')->ensureIndexes();

        $query = new \Model\MessageQuery($this->mongator->getRepository('Model\Message'));
        $query
            ->limit(5)
            ->fields(array('author' => 1));

        $result = $query->execute();
        $this->assertInstanceOf('Mongator\Query\Result', $result);

        $this->assertSame(10, $query->count());

        foreach ($query->all() as $key => $document) {
            $this->assertSame($key, (string) $document->getId());
            $this->assertInstanceOf('Model\Message', $document);
        }
    }

    public function providerNotArrayOrNull()
    {
        return array(
            array(true),
            array(1),
            array('string'),
        );
    }

    public function providerNotValidIntOrNull()
    {
        return array(
            array(true),
            array(array(1, 2)),
            array(1.1),
        );
    }

    public function providerNotBoolean()
    {
        return array(
            array(1),
            array('true'),
            array(array(true)),
        );
    }
}
