<?php

declare(strict_types=1);

namespace App\Models;

use Kalnoy\Nestedset\NodeTrait;

/**
 * Class Comment
 *
 * @property int      $id
 * @property int      $socialite_user_id
 * @property int      $article_id
 * @property string   $content
 * @property bool     $is_audited
 * @property int      $_lft
 * @property int      $_rgt
 * @property int|null $parent_id
 */
class Comment extends Base
{
    use NodeTrait;

    public function getContentAttribute($value)
    {
        return $this->ubbToImage($value);
    }

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $this->imageToUbb($value);
    }

    public function article()
    {
        return $this->belongsTo(Article::class)->withDefault();
    }

    public function socialiteUser()
    {
        return $this->belongsTo(SocialiteUser::class)->withDefault();
    }

    public function parentComment()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id')->withDefault();
    }

    public function ubbToImage($content)
    {
        $ubb   = ['[Kiss]', '[Love]', '[Yeah]', '[啊！]', '[背扭]', '[顶]', '[抖胸]', '[88]', '[汗]', '[瞌睡]', '[鲁拉]', '[拍砖]', '[揉脸]', '[生日快乐]', '[摊手]', '[睡觉]', '[瘫坐]', '[无聊]', '[星星闪]', '[旋转]', '[也不行]', '[郁闷]', '[正Music]', '[抓墙]', '[撞墙至死]', '[歪头]', '[戳眼]', '[飘过]', '[互相拍砖]', '[砍死你]', '[扔桌子]', '[少林寺]', '[什么？]', '[转头]', '[我爱牛奶]', '[我踢]', '[摇晃]', '[晕厥]', '[在笼子里]', '[震荡]'];
        $count = count($ubb);
        $image = [];

        // 循环生成img标签
        for ($i = 1; $i <= $count; $i++) {
            $image[] = '<img src="' . asset('statics/emote/tuzki/' . $i . '.gif') . '" title="' . str_replace(['[', ']'], '', $ubb[$i - 1]) . '" alt="' . config('app.name') . '">';
        }

        return str_replace($ubb, $image, $content);
    }

    public function imageToUbb($content)
    {
        $content = html_entity_decode(htmlspecialchars_decode($content));
        // 删标签 去空格 转义
        $content = strip_tags(trim($content), '<img>');
        preg_match_all('/<img.*?title="(.*?)".*?>/i', $content, $img);
        $search  = $img[0];
        $replace = array_map(function ($v) {
            return '[' . $v . ']';
        }, $img[1]);
        $content = str_replace($search, $replace, $content);

        return clean(strip_tags($content));
    }
}
