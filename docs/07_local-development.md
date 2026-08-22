# ローカル開発環境

## 位置づけ

Docker Composeを使用し、Squash Platformのローカル開発環境を構築する。
本番環境はお名前.com レンタルサーバー RSプランであり、Dockerコンテナをそのまま本番へ配置することは前提としない。

## 構成

| サービス | 用途 | バージョン・状態 |
|---|---|---|
| `app` | Apache、PHP、Composer、CakePHP実行環境 | PHP 8.5、CakePHP 5.4（決定済み） |
| `db` | ローカルデータベース | MySQL 8.4 LTS（ローカル暫定、2026-08-18確認） |
| `node` | `frontend/`（Vue.js FRONT）の依存管理・ビルド用。Node.jsがホストにあれば不要 | Node.js 24（必要時のみ起動） |

本番MySQLのバージョン、照合順序、TLS接続条件は接続試験後に確定する。確認結果によってローカルMySQLのバージョンと照合順序を合わせる。

## 前提

- Docker Desktopが起動していること
- Docker Compose v2以降を使用できること
- 使用するポートが空いていること（初期値: Web `8080`、MySQL `3306`）

## 初回準備

PowerShellでリポジトリ直下へ移動し、ローカル設定を作成する。

```powershell
Copy-Item .env.example .env
```

`.env`はGit管理外である。開発用パスワードを変更する場合は、このファイルだけを編集する。その後、APIの依存関係をインストールする。

```powershell
docker compose run --rm app composer install
```

FRONT（`frontend/`）はNode.jsで独立して依存関係を管理する。ホストにNode.js 24系がある場合はそのまま使う。

```powershell
cd frontend
npm install
```

ホストにNode.jsを入れたくない場合は、代わりに`node`コンテナを使う。

```powershell
docker compose --profile tools run --rm --no-deps node npm install
```

## 基本操作

```powershell
# WebとMySQLを起動
docker compose up -d --build

# 状態を確認
docker compose ps

# ログを確認
docker compose logs -f app db

# CakePHPコマンドを実行
docker compose exec app bin/cake

# Composerを実行
docker compose exec app composer install

# 未適用のデータベースマイグレーションを確認
docker compose exec app bin/cake migrations status

# データベースマイグレーションを適用
docker compose exec app bin/cake migrations migrate

# 停止
docker compose down
```

APIは `http://localhost:8080` で確認する（`.env`の`APP_PORT`を変更した場合は、そのポートを使用する）。CakePHP自身のエラー・診断ページ以外の画面はここには無い。

## フロントエンド（Vue.js）

`frontend/`はAPI（`app/`）と別に管理するVueプロジェクトで、Viteの開発サーバーで動かす。ホストにNode.jsがある場合:

```powershell
cd frontend
npm run dev
```

`http://localhost:5173` で確認する。Node.jsをホストに入れていない場合は`node`コンテナ経由で同じことができる。

```powershell
docker compose --profile tools up -d
docker compose --profile tools exec node npm run dev -- --host
```

FRONTはAPIへ`fetch(..., { credentials: 'include' })`でJSON呼び出しを行い、Cookieベースのセッションをそのまま使う（`frontend/src/api/client.js`）。APIはCORSで許可するオリジンとメールリンクの遷移先を`.env`の`FRONTEND_PORT`（既定`5173`）から`http://localhost:<FRONTEND_PORT>`として組み立てる（`compose.yaml`の`app`サービスの`CORS_ALLOWED_ORIGINS`/`FRONTEND_BASE_URL`）。Viteの開発サーバーのポートを変えた場合は、`.env`の`FRONTEND_PORT`も同じ値に変更し、`docker compose up -d`でAPIコンテナを再起動する。

確認メール・パスワード再設定メールのリンクも同じ`http://localhost:<FRONTEND_PORT>`を指す（`Frontend.baseUrl`設定）。メール本文はDebugKitのMailパネルで確認する（`compose.yaml`の`EMAIL_TRANSPORT_DEFAULT_URL: "debug://"`により実際には送信されない）。

FRONTのビルド成果物（`frontend/dist/`）を確認する場合:

```powershell
cd frontend
npm run build
npm run preview
```

## テスト用アカウントの手動作成

自己登録（SCR-OPR-211、メール確認あり）・管理者専用ログインを経由せず、確認済みの状態でアカウントを直接作成できる。開発・確認用であり、本番では使用しない。

```powershell
# 運営者アカウント（確認済み状態で作成）
docker compose exec -e OPERATOR_BOOTSTRAP_PASSWORD='パスワード' app bin/cake create_operator --email=operator@example.com

# 管理者アカウント（docs/specifications/500_Admin.md §501、既定でsuper_admin）
docker compose exec -e ADMIN_BOOTSTRAP_PASSWORD='パスワード' app bin/cake create_admin --email=admin@example.com --name="管理者"
```

`*_BOOTSTRAP_PASSWORD`を省略した場合はコンソールで入力を求める（画面には表示される。CakePHPの`ConsoleIo`に非表示入力が無いため）。

`composer test`/`composer check`はテスト用DBと開発用DBが同一のため、実行するとここで作成したアカウントを含め全データが消える。確認作業のたびに再作成が必要。

## データベース接続

コンテナ間では、CakePHPからホスト名`db`、ポート`3306`で接続する。Composeは`DATABASE_URL`を`app`コンテナへ渡す。

ホストPCのデータベースクライアントから接続する場合は、初期状態で次を使用する。

| 項目 | 値 |
|---|---|
| ホスト | `127.0.0.1` |
| ポート | `3306`（`.env`の`MYSQL_PORT`） |
| データベース | `snick_platform` |
| ユーザー | `snick` |
| パスワード | `.env`の`MYSQL_PASSWORD` |

MySQLのデータはDockerの名前付きボリューム`mysql-data`へ保持する。`docker compose down`では削除されない。`docker compose down -v`はローカルデータを削除するため、意図して初期化する場合だけ使用する。

## データベース変更の管理

- テーブル、カラム、インデックス等の変更は`app/config/Migrations`のCakePHPマイグレーションで管理する。
- 既存マイグレーションを適用後に書き換えず、変更ごとに新しいマイグレーションを追加する。
- 初期マイグレーションでは、通常ユーザー、管理者、選手、イベント、イベントスタッフ、固定ロールおよびスタッフへのロール付与を作成する。
- 試合、スケジュール、オフライン同期等のテーブルは、未決定の物理設計を確定してから追加する。

## 秘密情報

- 本番データベース、SMTP、SSH等の認証情報を`.env.example`へ記載しない。
- 本番用設定をローカルDocker環境へ流用しない。
- `.env`、CakePHPの環境別ローカル設定、鍵ファイルをGitへコミットしない。
