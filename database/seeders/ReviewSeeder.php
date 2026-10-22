<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // ISBN => [[投稿者のemail, 評価, コメント], ...]（1冊2〜4件、合計32件）
        $reviews = [
            '9784101010014' => [
                ['suzuki@example.com', 5, '猫の視点から人間を皮肉る語り口が面白く、明治の空気が伝わってきました。'],
                ['tanaka@example.com', 4, '話が脱線しがちで長く感じる部分もありますが、苦沙弥先生たちの掛け合いは笑えます。'],
                ['sato@example.com', 3, '文体に慣れるまで時間がかかりました。注釈を読みながらゆっくり楽しむ本だと思います。'],
            ],
            '9784422100524' => [
                ['yamada@example.com', 5, '「議論に勝つ唯一の方法は議論を避けること」という原則で、職場での接し方が変わりました。'],
                ['tanaka@example.com', 5, '相手の名前を覚える、誠実に褒めるなど、すぐ実践できることばかりで読み返しています。'],
                ['takahashi@example.com', 4, 'エピソードが古いものの、内容は今でも通用します。営業職の人には特におすすめです。'],
            ],
            '9784873115658' => [
                ['yamada@example.com', 5, '変数名の付け方の章が特に役立ちました。新人研修の課題図書にしたいくらいです。'],
                ['suzuki@example.com', 4, 'コメントに「なぜ」を書くという指摘が刺さりました。薄くてすぐ読めるのも良いです。'],
                ['sato@example.com', 5, 'コードレビューで指摘されていたことの理由がわかり、レビューを受けるのが楽になりました。'],
                ['takahashi@example.com', 4, '例はC++やJavaが中心ですが、考え方はどの言語にもそのまま使えます。'],
            ],
            '9784863940246' => [
                ['suzuki@example.com', 4, '第2の習慣「終わりを思い描くことから始める」をきっかけに、自分のミッションを書き出しました。'],
                ['tanaka@example.com', 3, '内容は良いのですが分量が多く、一度で消化するのは難しかったです。'],
                ['sato@example.com', 5, '緊急ではないが重要なことに時間を使う、という考え方で毎週の予定の立て方が変わりました。'],
            ],
            '9784101010021' => [
                ['yamada@example.com', 4, '主人公のまっすぐさが気持ちよく、テンポも良いので一気に読めました。'],
                ['takahashi@example.com', 3, '学生時代に読んだときより、大人になってからの方が赤シャツたちの描写が面白く感じました。'],
            ],
            '9784309226712' => [
                ['yamada@example.com', 5, '虚構を信じる力が人類の協力を可能にした、という視点に驚きました。上下巻とも一気読みです。'],
                ['suzuki@example.com', 4, '農業革命は史上最大の詐欺だった、という章が刺激的でした。歴史の見方が変わります。'],
                ['tanaka@example.com', 5, '歴史・生物学・経済学を横断する構成で、知的好奇心が満たされる一冊でした。'],
            ],
            '9784048930598' => [
                ['suzuki@example.com', 4, '関数は1つのことだけを行うべき、という原則を意識するようになりました。'],
                ['tanaka@example.com', 3, 'Javaのサンプルコードが長く、後半のケーススタディは読み飛ばしてしまいました。'],
                ['sato@example.com', 4, 'リーダブルコードの次に読む本としてちょうど良いレベル感でした。'],
                ['takahashi@example.com', 5, 'テストコードもプロダクトコードと同じく綺麗に保つべき、という主張に納得しました。'],
            ],
            '9784478025819' => [
                ['yamada@example.com', 5, '「課題の分離」を知って、他人の評価を気にしすぎていた自分が楽になりました。'],
                ['sato@example.com', 4, '対話形式なので心理学の本なのに読みやすいです。青年の反論が自分の疑問と重なりました。'],
                ['takahashi@example.com', 3, '考え方には共感できるものの、実生活でどう実践するかは少しイメージしにくかったです。'],
            ],
            '9784163902302' => [
                ['suzuki@example.com', 4, '神谷さんの破天荒さと、それに憧れる徳永の距離感が切なかったです。'],
                ['sato@example.com', 3, '純文学らしい文章で好みは分かれそうですが、ラストのライブの場面は印象に残りました。'],
            ],
            '9784822289607' => [
                ['yamada@example.com', 5, '冒頭のクイズで見事に間違え、自分の思い込みに気づかされました。'],
                ['tanaka@example.com', 4, 'ニュースの数字を鵜呑みにせず、比較対象や割合で見る習慣がつきました。'],
                ['takahashi@example.com', 5, 'データで世界を見ることの大切さがわかる本です。仕事の資料作りにも活きています。'],
            ],
            '9784822251468' => [
                ['suzuki@example.com', 4, 'ただの箱が世界の物流を変えた経緯が、ビジネスの物語として面白く読めました。'],
                ['takahashi@example.com', 3, '港湾労働者の組合との交渉の話が長く、中盤は少し退屈でした。'],
            ],
        ];

        $userIds = User::pluck('id', 'email');

        foreach ($reviews as $isbn => $bookReviews) {
            $bookId = Book::where('isbn', $isbn)->value('id');

            foreach ($bookReviews as [$email, $rating, $comment]) {
                Review::create([
                    'user_id' => $userIds[$email],
                    'book_id' => $bookId,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);
            }
        }
    }
}
