<?php

declare(strict_types=1);

namespace Tests\Feature\Home;

use App\Models\Comment;
use App\Models\SocialiteUser;
use App\Notifications\Comment as CommentNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

class IndexControllerTest extends TestCase
{
    public function testIndex()
    {
        $this->get('/')->assertStatus(200);
    }

    public function testArticle()
    {
        $this->get('/article/1')->assertStatus(200);
    }

    public function testArticleNotFound()
    {
        $this->get('/article/100')->assertNotFound();
    }

    public function testCategory()
    {
        $this->get('/category/1')->assertStatus(200);
    }

    public function testCategoryNotFound()
    {
        $this->get('/category/100')->assertNotFound();
    }

    public function testTag()
    {
        $this->get('/tag/1')->assertStatus(200);
    }

    public function testTagNotFound()
    {
        $this->get('/tag/100')->assertNotFound();
    }

    public function testChat()
    {
        $this->get('/note')->assertStatus(200);
    }

    public function testGit()
    {
        $this->get('/openSource')->assertStatus(200);
    }

    public function testComment()
    {
        Notification::fake();
        $this->setupEmail();

        $content = '评论666<img src="http://baijunyao.com/statics/emote/tuzki/3.gif" title="Yeah" alt="test">';
        $email   = 'comment@test.com';
        $comment = [
            'article_id' => 1,
            'pid'        => 0,
        ];

        $this->loginByUserId(1)
            ->post('/comment', $comment + [
                'content' => $content,
                'email'   => $email,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('comments', $comment + [
            'content' => (new Comment())->imageToUbb($content),
        ]);

        static::assertEquals($email, SocialiteUser::where('id', 1)->value('email'));

        Notification::assertSentTo(new AnonymousNotifiable(), CommentNotification::class, function (CommentNotification $notification) {
            return $notification->subject === '白俊遥 评论 欢迎使用 laravel-bjyblog';
        });

        $this->loginByUserId(1)
            ->post('/comment', $comment + [
                'content' => $content,
                'email'   => $email,
            ])
            ->assertStatus(302);
    }

    public function testReplyComment()
    {
        Notification::fake();
        $this->setupEmail();

        Comment::where('id', 1)->update([
            'socialite_user_id' => 2,
        ]);
        $socialiteUser1 = SocialiteUser::withTrashed()->find(1);
        $socialiteUser1->update([
            'is_admin' => 1,
        ]);
        $socialiteUser2 = SocialiteUser::withTrashed()->find(2);
        $socialiteUser2->update([
            'deleted_at' => null,
        ]);

        $content = '评论666<img src="http://baijunyao.com/statics/emote/tuzki/3.gif" title="Yeah" alt="test">';
        $email   = 'comment@test.com';
        $comment = [
            'article_id' => 1,
            'pid'        => 1,
        ];

        $this->loginByUserId($socialiteUser1->id)
            ->post('/comment', $comment + [
                'content' => $content,
                'email'   => $email,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('comments', $comment + [
            'content' => (new Comment())->imageToUbb($content),
        ]);

        Notification::assertSentTo($socialiteUser2, CommentNotification::class, function (CommentNotification $notification) {
            return $notification->subject === '白俊遥 回复 欢迎使用 laravel-bjyblog';
        });
    }

    public function testCommentNoMailConfigured()
    {
        Notification::fake();

        $content = '评论666<img src="http://baijunyao.com/statics/emote/tuzki/3.gif" title="Yeah" alt="test">';
        $comment = [
            'article_id' => 1,
            'pid'        => 1,
        ];

        /** For @see \App\Observers\CommentObserver::created() */
        config([
            'bjyblog.notification_email' => 'test@test.com',
        ]);

        $this->loginByUserId(1)
            ->post('/comment', $comment + [
                'content' => $content,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('comments', $comment + [
            'content' => (new Comment())->imageToUbb($content),
        ]);

        Notification::assertNotSentTo(new AnonymousNotifiable(), CommentNotification::class);
    }

    public function testCommentEmptyContent()
    {
        $comment = [
            'article_id' => 1,
            'pid'        => 0,
            'content'    => '',
        ];
        $this->loginByUserId(1)
            ->post('/comment', $comment)
            ->assertStatus(302);
    }

    public function testCommentInvalidContent()
    {
        $comment = [
            'article_id' => 1,
            'pid'        => 0,
            'content'    => 'test',
        ];
        $this->loginByUserId(1)
            ->post('/comment', $comment)
            ->assertStatus(302);
    }

    public function testCommentNotLoggedIn()
    {
        $comment = [
            'article_id' => 1,
            'pid'        => 0,
            'content'    => '评论666',
        ];
        $this->post('/comment', $comment)->assertStatus(401);
    }

    public function testCheckLoginWhenLogin()
    {
        auth()->guard('socialite')->loginUsingId(1);

        $this->get('/checkLogin')->assertStatus(200)->assertJson([
            'status' => 1,
        ]);
    }

    public function testCheckLoginWhenLogout()
    {
        $this->get('/checkLogin')->assertStatus(200)->assertJson([
            'status' => 0,
        ]);
    }

    public function testSearch()
    {
        $this->get('/search?wd=laravel')->assertOk();
    }

    public function testSearchForRobot()
    {
        $this->get('/search?wd=laravel', [
            'user-agent' => 'Mozilla/5.0 (Linux;u;Android 4.2.2;zh-cn;) AppleWebKit/534.46 (KHTML,like Gecko) Version/5.1 Mobile Safari/10600.6.3 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
        ])->assertNotFound();
    }

    public function testFeed()
    {
        $this->get('/feed')->assertStatus(200);
    }
}
