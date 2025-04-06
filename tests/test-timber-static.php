<?php

/**
 * @group posts-api
 */
class TestTimberStaticPages extends Timber_UnitTestCase
{
    public function tear_down()
    {
        update_option('show_on_front', 'posts');
        update_option('page_on_front', '0');
        update_option('page_for_posts', '0');
    }

    public function testPageAsPostsPage()
    {
        $pids = $this->factory->post->create_many(6);
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
        ]);
        update_option('page_for_posts', $page_id);
        $this->go_to(home_url('/?page_id=' . $page_id));
        $page = Timber::get_post();
        $this->assertEquals($page_id, $page->ID);
    }

    public function testPageAsJustAPage()
    {
        $pids = $this->factory->post->create_many(6);
        $page_id = $this->factory->post->create([
            'post_title' => 'Foobar',
            'post_name' => 'foobar',
            'post_type' => 'page',
        ]);
        $this->go_to(home_url('/?page_id=' . $page_id));
        $page = Timber::get_post();
        $this->assertEquals($page_id, $page->ID);
    }

    public function testPageAsStaticFront()
    {
        $pids = $this->factory->post->create_many(6);
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
        ]);
        update_option('page_on_front', $page_id);
        $this->go_to(home_url('/'));
        global $wp_query;
        $wp_query->queried_object = get_post($page_id);
        $page = Timber::get_post();
        $this->assertEquals($page_id, $page->ID);
    }

    public function testFrontPageAsPage()
    {
        $spaceballs = "What's the matter, Colonel Sandurz? Chicken?";
        $page_id = $this->factory->post->create([
            'post_title' => 'Spaceballs',
            'post_content' => $spaceballs,
            'post_type' => 'page',
        ]);
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_id);
        $this->go_to(home_url('/'));
        $post = Timber::get_post();
        $this->assertEquals($page_id, $post->ID);
    }

    public function testStaticPostPage()
    {
        $this->clearPosts();
        $page_id = $this->factory->post->create([
            'post_title' => 'Gobbles',
            'post_type' => 'page',
        ]);
        $posts = $this->factory->post->create_many(10, [
            'post_title' => 'Timmy',
        ]);

        update_option('show_on_front', 'page');
        update_option('page_for_posts', $page_id);
        $this->go_to(get_permalink($page_id));

        $posts = Timber::get_posts();

        $this->assertEquals('Timmy', $posts[0]->title());
    }

    public function testOtherPostOnStaticPostPage()
    {
        $page_id = $this->factory->post->create([
            'post_title' => 'Gobbles',
            'post_type' => 'page',
        ]);
        update_option('page_for_posts', $page_id);
        $post_id = $this->factory->post->create([
            'post_title' => 'My Real post',
            'post_type' => 'post',
        ]);
        $this->go_to(home_url('/?p=' . $page_id));

        $post = Timber::get_post($post_id);
        $this->assertEquals($post_id, $post->ID);
    }

    public function testRegularStaticPage()
    {
        $this->markTestSkipped('@todo what is this testing?');
        $page_id = $this->factory->post->create([
            'post_title' => 'Mister Slave',
            'post_type' => 'page',
        ]);
        $children = $this->factory->post->create_many(10, [
            'post_title' => 'Timmy',
        ]);
        $this->go_to(home_url('/?p=' . $page_id));

        $posts = Timber::get_posts();
        $this->assertCount(0, $posts);

        $page = Timber::get_post();
        $this->assertEquals($page_id, $page->ID);
    }

    public function testRegularStaticPageFlipped()
    {
        $this->markTestSkipped('@todo what is this testing?');
        $page_id = $this->factory->post->create([
            'post_title' => 'Mister Slave',
            'post_type' => 'page',
        ]);
        $children = $this->factory->post->create_many(10, [
            'post_title' => 'Timmy',
        ]);
        $this->go_to(home_url('/?p=' . $page_id));

        $page = Timber::get_post();
        $this->assertEquals($page_id, $page->ID);

        $posts = Timber::get_posts();
        $this->assertCount(0, $posts);
    }
}
