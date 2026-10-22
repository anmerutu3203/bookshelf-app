# BookShelf 書籍レビューアプリ（COACHTECH 模擬案件）

要件定義書（詳細度50%）: https://docs.google.com/spreadsheets/d/1Pokkxjwu3ClL6JJ_C8rw7ITutUe2H652yuYHgaJ0RBM
本ファイルはその要約。食い違ったらスプレッドシートが正。

## 前提

- バックエンドのみ実装する（DB設計・認証/認可・CRUD・公開API・テスト）。Blade は提供済みなので、フロントは作らない。
- 「何を作るか」は決まっているが、「どう作るか」は未確定のまま渡されている。リダイレクト先・フラッシュメッセージの文言・バリデーションルール・エラーメッセージ・テストケース・API の JSON 構造はこちらで設計し、コーチ（PM 役）の承認を得てから実装する。
  - 上記の未確定事項を勝手に確定扱いにしない。必ず「〜と設計しましたが問題ないですか？」の形で仮説を添えて提示する。
- ★ は応用要件。基本要件が終わるまで手を付けない。
  - フェーズ1（基本）: Blade は `Preparedblade-mockcase-BookShelf` の Basic ブランチから取得する。
  - フェーズ2（応用）: 同リポジトリの Advanced ブランチで `resources/` を差し替え、マイグレーションを書き直して `migrate:fresh --seed` する。

## 技術スタック

PHP 8.5 / Laravel 10.x / MySQL 8.4 / Laravel Sail / phpMyAdmin / Vite / Tailwind CSS ^3.4.0 + @tailwindcss/forms / Alpine.js
認証は **Laravel Fortify**、API 認証（★）は **Sanctum**。

- 日本語化: `config/app.php` の `locale` を `ja` にし、`lang/ja/` にメッセージファイルを手で置く。
  - **`laravel-lang/*` 系パッケージは導入禁止**（2026年5月のサプライチェーン攻撃で悪用されたため）。
- 設定値（DB・API キー）は `.env` で管理する。ハードコードしない。DB_HOST は `mysql`。

## コマンド

```bash
sail up -d
sail npm run dev                 # 開発中は常時起動しておく
sail artisan migrate:fresh --seed
sail artisan test --coverage     # 目標: 基本は60%超、応用込みで80%以上
sail bin pint --test             # 採点基準: 「No fixable issues were found」と出ること
vendor/bin/pint                  # コミット前に必ず実行する
```

## アーキテクチャ（Traditional Web + 公開API）

- Web: `routes/web.php` と `app/Http/Controllers/`。Blade とセッション認証で動かす。
- API: `routes/api.php` と `app/Http/Controllers/Api/V1/`。レスポンスは API Resource で整形する。
- ルーティングは下の「画面定義」に、挙動は「機能要件」に従う。指定外の技術で実装しない。

## コーディング規約（採点対象）

- 命名は PSR-12 に準拠する。
  - 変数・メソッド: camelCase
  - クラス・モデル・コントローラー・FormRequest・Seeder: PascalCase
  - テーブル: snake_case 複数形、カラム: snake_case 単数形、マイグレーションファイル: snake_case
  - `a`・`x` のような無意味な名前や、ローマ字（例: okyakusama）は使わない。
- DB 操作は Eloquent を使う。クエリビルダや生 SQL は原則使わない。N+1 は `with()` で防ぐ。
- コントローラーはリクエストの受付とレスポンスの返却だけを担当する。ビジネスロジックはモデルかサービスに置く。
- バリデーションは必ず FormRequest に分離する。全 FormRequest に `messages()` を実装し、日本語のメッセージを定義する。
- 認可は必ず Policy に書き、コントローラーから `$this->authorize()` で呼ぶ。
- Git:
  - 機能ごとにブランチを切って main にマージする。
  - コミットメッセージは `feat: 書籍一覧表示を実装` のような形式にする。
- ★応用で追加される規約:
  - コントローラー・モデル・FormRequest・Policy の全メソッドに引数と戻り値の型を書く。リレーションの戻り値は `HasMany` などの具体型にする。
  - 主要メソッドには PHPDoc を書く。
  - foreach ではなく Collection メソッド（map / filter / groupBy / flatMap）を使う。特にマイ読書レポートで徹底する。
  - 外部 API の呼び出しは `Http` ファサードで行い、テストでは `Http::fake()` を使う。
  - バッチは Schedule + Console Command で実装する。
  - 複数のテーブルを更新する処理は `DB::transaction()` で囲む。
  - 通知は Notification の DatabaseChannel に保存する。
  - 値の種類が決まっているものは PHP Enum にし、`casts()` で紐付ける。

## 画面定義

| ID | 画面 | パス | 備考 |
|---|---|---|---|
| PG01 | 書籍一覧 | GET `/` or `/books` | 公開。10件/ページで最新順。各書籍にジャンルを表示 |
| PG02 | 書籍詳細 | GET `/books/{book}` | 公開。レビュー・ジャンル・お気に入り・いいね数を表示 |
| PG03 | 書籍登録 | GET `/books/create` | 認証必須。全ジャンルをチェックボックスで表示 |
| PG04 | 書籍編集 | GET `/books/{book}/edit` | 作成者のみ |
| PG05 | ジャンル一覧 | GET `/genres` | 認証必須。ジャンルごとの書籍数を表示 |
| PG06 | ジャンル詳細 | GET `/genres/{genre}` | 認証必須。10件/ページ |
| PG07 | ジャンル登録 | GET `/genres/create` | 認証必須 |
| PG08 | ジャンル編集 | GET `/genres/{genre}/edit` | 認証必須 |
| PG09 | レビュー編集 | GET `/reviews/{review}/edit` | 投稿者のみ |
| PG10 | お気に入り一覧 | GET `/favorites` | 認証必須。10件/ページ |
| PG11 | ランキング | GET `/ranking` | 公開。平均評価の TOP10（レビューがない書籍は出さない） |
| PG12 | ログイン | GET `/login` | |
| PG13 | 会員登録 | GET `/register` | |
| ★PG14 | マイ読書レポート | GET `/reports` | 認証必須 |
| ★PG15 | 読書計画一覧 | GET `/reading-plans` | 認証必須。`?status=` で絞り込む |
| ★PG16 | 読書計画作成 | GET `/reading-plans/create` | 書籍のプルダウンと期日を入力 |
| ★PG17 | 読書計画編集 | GET `/reading-plans/{plan}/edit` | 所有者のみ。期日だけ変更できる |
| ★PG18 | 通知一覧 | GET `/notifications` | 各通知に既読化の操作を付ける |

## 機能要件（操作系）

全体の共通ルール:
- 未認証のユーザーが認証必須ページに来たら `/login` にリダイレクトする。
- ログイン済みのユーザーが `/login`・`/register` に来たら `/` にリダイレクトする。

- 認証（Fortify）: `POST /register`、`POST /login`、`POST /logout`（ログアウト後はログイン画面へ）
- 書籍:
  - `POST /books`（ジャンルの紐付けも作る）
  - `PUT /books/{book}`
  - `DELETE /books/{book}`（レビュー・お気に入り・ジャンル紐付けも処理する。作成者のみ）
- お気に入り: `POST /books/{book}/favorites` でトグル
- レビュー:
  - `POST /books/{book}/reviews`
  - `PUT /reviews/{review}`
  - `DELETE /reviews/{review}`（関連するいいねも処理する。投稿者のみ）
- いいね: `POST /reviews/{review}/like` でトグル
- ジャンル: `POST /genres`、`PUT /genres/{genre}`、`DELETE /genres/{genre}`（書籍が紐付いている場合は削除を制限する）
- ★書籍一覧の検索: `?keyword=`（title か author の部分一致）、`?genre=`、`?sort=`
  - sort の値: `latest`（既定）/ `oldest` / `title`（昇順）/ `rating`（評価順。レビューがない書籍は最後）
  - ページネーションでも検索条件を引き継ぐ。
- ★ISBN 検索: `GET /books/isbn/{isbn}`（13桁）で Google Books API から書籍情報を取得し、フォームに自動入力する。エラー時も適切なレスポンスを返す。
- ★マイ読書レポート:
  1. 総レビュー数、読了冊数（ユニークな書籍数）、平均評価
  2. 星1〜5ごとの件数（横バー）
  3. 星4以上の書籍 TOP5（書籍詳細へのリンク付き）
  4. ジャンル別の平均評価と件数 TOP5（ジャンル詳細へのリンク付き）
- ★読書計画: `POST /reading-plans`、`PUT /reading-plans/{plan}`、`DELETE /reading-plans/{plan}`、`POST /reading-plans/{plan}/complete`（いずれも所有者のみ）
- ★通知: `POST /notifications/{id}/read` で既読にする。
- ★日次バッチ:
  - 期日を過ぎた計画のステータスを自動で変更する。
  - 条件を満たす計画にリマインダー通知を送る。
  - 詳細な条件は PM と詰める（Seeder のシナリオ参照: 3日前・当日・期限切れ・期限切れから3日後の再エンゲージメント）。

## 公開API（/api/v1）

| メソッド | URI | 基本 | ★応用 |
|---|---|---|---|
| GET | `/api/v1/books` | 認証不要 | 認証不要 |
| GET | `/api/v1/books/{book}` | 認証不要 | 認証不要 |
| POST | `/api/v1/books` | 認証不要 | Sanctum 必須 |
| PUT | `/api/v1/books/{book}` | 認証不要 | Sanctum + BookPolicy（所有者のみ） |
| DELETE | `/api/v1/books/{book}` | 認証不要 | Sanctum + BookPolicy（所有者のみ） |

- 一覧:
  - keyword・ジャンル ID・page・per_page による検索とページネーションに対応する。
  - 各書籍にジャンル・平均評価・レビュー件数を含める。
- 詳細: ジャンルとレビュー（投稿者名・評価・コメント・投稿日時）を含める。
- 存在しない ID にはエラーレスポンスを返す。
- 登録・削除の成功時は適切なステータスコードを返す。
- バリデーションエラーは日本語で返す。
- ★応用:
  - Bearer トークンで認証する。未認証時と認可エラー時は適切なステータスを返す。
  - 登録者 ID のバリデーションを見直す。

## バリデーション方針（具体的なルールは自分で設計する）

- 書籍:
  - タイトル・著者などが必須。
  - ISBN は桁数と一意性をチェックする。編集時は自分自身を除外する。
  - 出版日は有効な日付。ジャンルは1つ以上。画像 URL は入力されていれば URL 形式。
  - ★応用では一部の項目を nullable にする。
- API 書籍登録・編集: Web 版と同等のルールに加え、登録者 ID の妥当性もチェックする。
- API 一覧: keyword・ジャンル ID・page・per_page をチェックする。
- レビュー: 評価値の範囲をチェックする。
- ジャンル: 名前は必須かつ一意。編集時は自分自身を除外する。
- 会員登録: 名前・メール・パスワードをチェックする。メールは一意。
- ログイン: メールとパスワードが必須。
- ★読書計画: ルール・重複の制御・編集の制限を設計する。

## データ（テーブル構造と ER 図は自分で設計し、README にも ER 図を載せる）

| データ | 管理する情報 |
|---|---|
| books | タイトル、著者、ISBN（unique）、出版日（★応用で nullable）、説明（任意）、画像 URL（任意）、作成者 ID |
| genres | 名前（unique） |
| book_genre（中間） | 書籍 ID + ジャンル ID |
| reviews | 投稿者、書籍、評価（1〜5）、コメント |
| favorites（中間） | ユーザー ID + 書籍 ID |
| review_likes（中間） | ユーザー ID + レビュー ID |
| users | 名前、メール（unique）、パスワード（ハッシュ化） |
| Laravel 標準 | パスワードリセット、personal_access_tokens、failed_jobs |
| ★reading_plans | 計画者、書籍、期日、状態、関連する日時（構造は自分で設計する） |
| ★notifications | Laravel 標準の通知テーブル |

- 外部キーとカスケード削除を適切に設定する。中間テーブルは「複合主キー」か「id + unique 制約」のどちらにするかを決める。
- ★応用版の users では、Fortify の two_factor 系3カラムは不要。

## シーディング（忘れると再提出か大幅減点）

`DatabaseSeeder` から次の順で呼ぶ: User → Genre → Book → Review → Favorite → ReviewLike（★ → ReadingPlan）

- **UserSeeder**: `firstOrCreate`（email で重複を防ぐ）と `Hash::make()` を使う。password はすべて `password`。
  - 山田太郎 yamada@example.com
  - 鈴木花子 suzuki@example.com
  - 田中一郎 tanaka@example.com
  - 佐藤美咲 sato@example.com
  - 高橋健太 takahashi@example.com
- **GenreSeeder**: `firstOrCreate` で次の10件を入れる。
  - 小説 / ビジネス / 技術書 / 自己啓発 / エッセイ / 歴史 / 科学 / 芸術 / 料理 / 旅行
- **BookSeeder**: 下の11冊を入れる。
  - 登録者は `User::first()`。★応用では `$users->random()->id` に変える。
  - `firstOrCreate`（ISBN で重複を防ぐ）と `genres()->sync()` を使う。
  - 各書籍に description を入れる。image_url は `https://placehold.co/200x300/e2e8f0/475569?text={1〜11}`。
  1. 吾輩は猫である / 夏目漱石 / 9784101010014 / 1905-01-01 / 小説
  2. 人を動かす / D・カーネギー / 9784422100524 / 1936-10-01 / ビジネス, 自己啓発
  3. リーダブルコード / Dustin Boswell / 9784873115658 / 2012-06-23 / 技術書
  4. 7つの習慣 / スティーブン・R・コヴィー / 9784863940246 / 2013-08-30 / ビジネス, 自己啓発
  5. 坊っちゃん / 夏目漱石 / 9784101010021 / 1906-04-01 / 小説
  6. サピエンス全史 / ユヴァル・ノア・ハラリ / 9784309226712 / 2016-09-08 / 歴史, 科学
  7. Clean Code / Robert C. Martin / 9784048930598 / 2017-12-18 / 技術書
  8. 嫌われる勇気 / 岸見一郎・古賀史健 / 9784478025819 / 2013-12-13 / 自己啓発
  9. 火花 / 又吉直樹 / 9784163902302 / 2015-03-11 / 小説
  10. FACTFULNESS / ハンス・ロスリング / 9784822289607 / 2019-01-11 / ビジネス, 科学
  11. コンテナ物語 / マルク・レビンソン / 9784822251468 / 2007-01-18 / ビジネス, 歴史
- **ReviewSeeder**: `create` で32件入れる。
  - rating は 3〜5。1冊あたり2〜4件。コメントは具体的な内容にする。
  - ★応用での変更:
    - rating を 1〜5 に広げ、1冊あたりの件数を `rand(2,4)`、投稿者を `$users->random($reviewCount)` にする。
    - コメントは評価ごとに次のテンプレートから選ぶ。
      - 5: 素晴らしい本でした！ / 人生が変わりました。 / 何度も読み返しています。
      - 4: とても参考になりました。 / 読みやすくておすすめです。 / 期待通りの内容でした。
      - 3: 普通でした。 / 可もなく不可もなく。 / 期待したほどではなかった。
      - 2: 少し期待外れでした。 / 内容が薄い印象。 / もう少し深掘りしてほしかった。
      - 1: 残念ながら合いませんでした。 / 期待と違いました。
- **FavoriteSeeder**: 各ユーザーに3〜5冊。`syncWithoutDetaching` を使う。
- **ReviewLikeSeeder**: 各レビューに0〜3人。自分のレビューにはいいねしない。`syncWithoutDetaching` を使う。
- ★**ReadingPlanSeeder**: `create` で6件入れる。日付は `Carbon::today()` を基準にする。book_id は計画ごとに別の書籍にする。
  - 山田太郎:
    1. +3日 in_progress（3日前リマインダーの対象）
    2. 今日 in_progress（当日リマインダーの対象）
    3. -3日 in_progress（自動失効 + 3日後の再エンゲージメントの対象）
    4. +7日 in_progress（対象外）
    5. -10日 completed（completed_at は -5日）
  - 鈴木花子:
    6. +5日 in_progress（山田でログインして `/reading-plans/6/edit` を開くと 403 になることの確認用）

## テスト

- 全テストが通ること。カバレッジは基本で60%超、応用込みで80%以上。採点は機能カテゴリ単位で行われる。
- Unit: モデル
- Feature: 画面アクセス / 書籍CRUD / レビュー / ジャンル / お気に入り / いいね / ランキング / 認証 / 公開API
- ★Feature: 検索・フィルタ / ソート / 認可の詳細 / ISBN 検索（`Http::fake()`）/ マイ読書レポート / Sanctum / 読書計画 / 期限変更 / リマインダーバッチ / 自動失効バッチ

## README に必ず書く項目

プロジェクト名、概要、ER 図、環境構築手順、使用技術、作成者、API エンドポイント一覧、開発環境 URL（http://localhost）
