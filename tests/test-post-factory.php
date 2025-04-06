<?php

use Timber\Factory\PostFactory;
use Timber\Post;
use Timber\PostArrayObject;
use Timber\PostQuery;

class MyPost extends Post
{
}
class MyPage extends Post
{
}
class MyCustom extends Post
{
}
class MySpecialCustom extends MyCustom
{
}

/**
 * @group factory
 * @group post-collections
 */
class TestPostFactory extends Timber_UnitTestCase
{
    public function testFrom()
    {
        $post_id = $this->factory->post->create([
            'post_type' => 'post',
        ]);
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
        ]);
        $custom_id = $this->factory->post->create([
            'post_type' => 'custom',
        ]);

        $postFactory = new PostFactory();
        $post = $postFactory->from($post_id);
        $page = $postFactory->from($page_id);
        $custom = $postFactory->from($custom_id);

        // Assert that all instances are of Timber\Post
        $this->assertInstanceOf(Post::class, $post);
        $this->assertInstanceOf(Post::class, $post);
        $this->assertInstanceOf(Post::class, $custom);
    }

    public function testFromInvalidId()
    {
        $postFactory = new PostFactory();
        $post = $postFactory->from(99999);

        $this->assertNull($post);
    }

    public function testFromIdString()
    {
        $post_id = $this->factory->post->create();

        $postFactory = new PostFactory();
        $post = $postFactory->from('' . $post_id);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals($post_id, $post->id);
    }

    public function testFromWithClassmapFilter()
    {
        $my_class_map = function () {
            return [
                'post' => MyPost::class,
                'page' => MyPage::class,
                'custom' => MyCustom::class,
            ];
        };

        $this->add_filter_temporarily('timber/post/classmap', $my_class_map);

        $post_id = $this->factory->post->create([
            'post_type' => 'post',
        ]);
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
        ]);
        $custom_id = $this->factory->post->create([
            'post_type' => 'custom',
        ]);

        $postFactory = new PostFactory();
        $post = $postFactory->from($post_id);
        $page = $postFactory->from($page_id);
        $custom = $postFactory->from($custom_id);

        $this->assertTrue(MyPost::class === get_class($post));
        $this->assertTrue(MyPage::class === get_class($page));
        $this->assertTrue(MyCustom::class === get_class($custom));
    }

    public function testFromWithClassFilter()
    {
        $my_class_filter = function ($class, WP_Post $post) {
            return MyCustom::class;
        };

        $this->add_filter_temporarily('timber/post/class', $my_class_filter, 10, 2);

        $custom_id = $this->factory->post->create([
            'post_type' => 'custom',
        ]);

        $postFactory = new PostFactory();
        $custom = $postFactory->from($custom_id);

        $this->assertTrue(MyCustom::class === get_class($custom));
    }

    public function testFromWithCallable()
    {
        $my_class_map = function (array $map) {
            return array_merge($map, [
                'page' => function () {
                    return MyPage::class;
                },
                'custom' => function (WP_Post $post) {
                    if ($post->post_name === 'my-special-post') {
                        return MySpecialCustom::class;
                    }
                    return MyCustom::class;
                },
            ]);
        };

        $this->add_filter_temporarily('timber/post/classmap', $my_class_map);

        $post_id = $this->factory->post->create([
            'post_type' => 'post',
        ]);
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
        ]);
        $custom_id = $this->factory->post->create([
            'post_type' => 'custom',
        ]);
        $special_id = $this->factory->post->create([
            'post_type' => 'custom',
            'post_name' => 'my-special-post',
        ]);

        $postFactory = new PostFactory();
        $post = $postFactory->from($post_id);
        $page = $postFactory->from($page_id);
        $custom = $postFactory->from($custom_id);
        $special = $postFactory->from($special_id);

        $this->assertTrue(Post::class === get_class($post));
        $this->assertTrue(MyPage::class === get_class($page));
        $this->assertTrue(MyCustom::class === get_class($custom));
        $this->assertTrue(MySpecialCustom::class === get_class($special));
    }

    public function testFromWpPost()
    {
        $post_id = $this->factory->post->create([
            'post_type' => 'page',
            'post_title' => 'Title One',
        ]);

        $postFactory = new PostFactory();
        $this->assertInstanceOf(Post::class, $postFactory->from(get_post($post_id)));
    }

    public function testFromWpQuery()
    {
        $my_class_map = function () {
            return [
                'post' => MyPost::class,
                'page' => MyPage::class,
                'custom' => MyCustom::class,
            ];
        };

        $this->add_filter_temporarily('timber/post/classmap', $my_class_map);

        $post_id = $this->factory->post->create([
            'post_type' => 'post',
            'post_date' => '2020-01-10 19:46:41',
        ]);
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
            'post_date' => '2020-01-09 19:46:41',
        ]);
        $custom_id = $this->factory->post->create([
            'post_type' => 'custom',
            'post_date' => '2020-01-08 19:46:41',
        ]);

        $postFactory = new PostFactory();
        $query = new WP_Query([
            'post_type' => ['post', 'page', 'custom'],
        ]);
        $posts = $postFactory->from($query);

        $this->assertTrue(MyPost::class === get_class($posts[0]));
        $this->assertTrue(MyPage::class === get_class($posts[1]));
        $this->assertTrue(MyCustom::class === get_class($posts[2]));
    }

    public function testFromAcfArray()
    {
        $id = $this->factory->post->create([
            'post_type' => 'page',
            'post_title' => 'Title One',
        ]);

        $postFactory = new PostFactory();
        $post = $postFactory->from([
            'ID' => $id,
        ]);

        $this->assertEquals($id, $post->id);
    }

    public function testFromArray()
    {
        $this->factory->post->create([
            'post_type' => 'page',
            'post_title' => 'Title One',
        ]);
        $this->factory->post->create([
            'post_type' => 'page',
            'post_title' => 'Title Two',
        ]);

        $postFactory = new PostFactory();
        $res = $postFactory->from(get_posts('post_type=page'));

        $this->assertTrue(true, is_array($res));
        $this->assertCount(2, $res);
        $this->assertInstanceOf(Post::class, $res[0]);
        $this->assertInstanceOf(Post::class, $res[1]);
    }

    public function testFromArrayCustom()
    {
        $my_class_map = function (array $map) {
            return array_merge($map, [
                'page' => MyPage::class,
                'custom' => MyCustom::class,
            ]);
        };

        $this->add_filter_temporarily('timber/post/classmap', $my_class_map);

        $postFactory = new PostFactory();

        $this->factory->post->create([
            'post_type' => 'post',
            'post_title' => 'AAA',
        ]);
        $this->factory->post->create([
            'post_type' => 'page',
            'post_title' => 'BBB',
        ]);
        $this->factory->post->create([
            'post_type' => 'custom',
            'post_title' => 'CCC',
        ]);

        $res = $postFactory->from(get_posts([
            'post_type' => ['custom', 'page', 'post'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]));

        $this->assertTrue(true, is_array($res));
        $this->assertCount(3, $res);
        $this->assertTrue(Post::class === get_class($res[0]));
        $this->assertTrue(MyPage::class === get_class($res[1]));
        $this->assertTrue(MyCustom::class === get_class($res[2]));
    }

    public function testFromAssortedArray()
    {
        $a_id = $this->factory->post->create([
            'post_type' => 'post',
            'post_title' => 'AAA',
        ]);
        $b_id = $this->factory->post->create([
            'post_type' => 'post',
            'post_title' => 'BBB',
        ]);
        $c_id = $this->factory->post->create([
            'post_type' => 'post',
            'post_title' => 'CCC',
        ]);

        $postFactory = new PostFactory();

        // pass in an ID, a WP_Post instance, and a Timber\Post instance
        $res = $postFactory->from([
            $a_id,
            get_post($b_id),
            $postFactory->from($c_id),
        ]);

        // Here we're operating on a PostArrayObject, which implements ArrayObject/ArrayAccess.
        $this->assertInstanceOf(PostArrayObject::class, $res);

        $this->assertInstanceOf(Post::class, $res[0]);
        $this->assertInstanceOf(Post::class, $res[1]);
        $this->assertInstanceOf(Post::class, $res[2]);
    }

    public function testFromQueryArray()
    {
        $my_class_map = function (array $map) {
            return array_merge($map, [
                'page' => MyPage::class,
                'custom' => MyCustom::class,
            ]);
        };

        $this->add_filter_temporarily('timber/post/classmap', $my_class_map);

        $this->factory->post->create([
            'post_type' => 'post',
            'post_title' => 'AAA',
        ]);
        $this->factory->post->create([
            'post_type' => 'page',
            'post_title' => 'BBB',
        ]);
        $this->factory->post->create([
            'post_type' => 'custom',
            'post_title' => 'CCC',
        ]);
        $this->factory->post->create([
            'post_type' => 'other_thing',
            'post_title' => 'ZZZ',
        ]);

        $postFactory = new PostFactory();

        $res = $postFactory->from([
            'post_type' => ['post', 'page', 'custom'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Here we're operating on a PostQuery, which implements ArrayAccess.
        $this->assertTrue(PostQuery::class === get_class($res));

        $this->assertTrue(Post::class === get_class($res[0]));
        $this->assertTrue(MyPage::class === get_class($res[1]));
        $this->assertTrue(MyCustom::class === get_class($res[2]));
    }

    /**
     * @expectedIncorrectUsage The `Timber\PostClassMap` filter
     */
    public function testDeprecatedPostClassMapFilter()
    {
        $my_class_map = function () {
            return [
                'custom' => MyCustom::class,
            ];
        };

        $this->add_filter_temporarily('Timber\PostClassMap', $my_class_map);

        $this->factory->post->create([
            'post_type' => 'custom',
            'post_title' => 'CCC',
        ]);

        $post_factory = new PostFactory();
        $res = $post_factory->from([
            'post_type' => ['custom'],
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $this->assertTrue(Post::class === get_class($res[0]));
    }
}
