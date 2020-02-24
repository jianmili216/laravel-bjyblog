<?php

declare(strict_types=1);

namespace App\Console\Commands\Upgrade;

use App\Models\Article;
use App\Models\Comment;
use DB;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class V6_16_0 extends Command
{
    protected $signature   = 'upgrade:v6.16.0';
    protected $description = 'Upgrade to v6.16.0';

    private $children = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (!Schema::hasTable('comment_backups')) {
            Schema::rename('comments', 'comment_backups');
            Schema::table('comment_backups', function (Blueprint $table) {
                $table->renameColumn('pid', 'parent_id');
            });

            Schema::create('comments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('socialite_user_id')->unsigned()->default(0);
                $table->integer('article_id')->unsigned();
                $table->text('content');
                $table->boolean('is_audited');
                $table->nestedSet();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        DB::table('comments')->delete();

        // Disable model event
        config([
            'mail.password'         => '',
            'bjyblog.comment_audit' => false,
        ]);

        $deletedComments = DB::table('comment_backups')
            ->whereNotNull('deleted_at')
            ->pluck('deleted_at', 'id');
        $deletedComment_ids = $deletedComments->keys();

        if ($deletedComment_ids->isNotEmpty()) {
            DB::table('comment_backups')->whereIn('id', $deletedComment_ids)->update([
                'deleted_at' => null,
            ]);
        }

        $this->info('Migration comments');
        $this->call('cache:clear');
        $article_ids = Article::withTrashed()->pluck('id');

        $bar = $this->output->createProgressBar($article_ids->count());
        $bar->start();

        foreach ($article_ids as $article_id) {
            $bar->advance();
            $comments = $this->getDataByArticleId($article_id);

            foreach ($comments as $comment) {
                Comment::create($comment);
            }
        }

        if ($deletedComments->isNotEmpty()) {
            foreach ($deletedComments as $id => $deleted_at) {
                DB::table('comments')->where('id', $id)->update([
                    'deleted_at' => $deleted_at,
                ]);
            }
        }

        $bar->finish();
    }

    private function getDataByArticleId($article_id)
    {
        $comments = DB::table('comment_backups')
            ->select('id', 'socialite_user_id', 'parent_id', 'article_id', 'content', 'is_audited', 'created_at', 'updated_at', 'deleted_at')
            ->where('article_id', $article_id)
            ->where('parent_id', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $comments = $comments->map(function ($comment) {
            return (array) $comment;
        })->toArray();

        foreach ($comments as $k => $v) {
            $this->children = [];
            $this->getTree($v);

            if (!empty($children = $this->children)) {
                uasort($children, function ($a, $b) {
                    $prev = $a['created_at'] ?? 0;
                    $next = $b['created_at'] ?? 0;

                    if ($prev === $next) {
                        return 0;
                    }

                    return ($prev < $next) ? -1 : 1;
                });
            }

            if ($children !== []) {
                $comments[$k]['children'] = $children;
            }
        }

        return $comments;
    }

    private function getTree($comment)
    {
        $children = DB::table('comment_backups')
            ->select('id', 'socialite_user_id', 'parent_id', 'article_id', 'content', 'is_audited', 'created_at', 'updated_at', 'deleted_at')
            ->where('parent_id', $comment['id'])
            ->orderBy('created_at', 'desc')
            ->get();

        $children = $children->map(function ($comment) {
            return (array) $comment;
        })->toArray();

        if (!empty($children)) {
            foreach ($children as $k => $v) {
                $this->children[] = $v;
                $this->getTree($v);
            }
        }
    }
}
