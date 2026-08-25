# ローカル開発環境

## 位置づけ

本書は、Squash Platformのローカル開発環境と、現在実行できる操作を管理する。本番環境はお名前.com レンタルサーバー RSプランであり、Dockerコンテナをそのまま本番へ配置しない。

## 現在の構成

2026年8月25日時点では、CakePHP APIを`app/`、Vue.js FRONTを`frontend/`で管理している。FRONTは`operator`と`admin`の独立アプリを実装済みである。

| サービス | 用途 | バージョン・状態 |
|---|---|---|
| `app` | Apache、PHP、Composer、CakePHP | PHP 8.5 / CakePHP 5.4 |
| `db` | ローカルデータベース | MySQL 8.4 LTS（2026年8月18日時点の暫定版） |
| `node` | `frontend/`のVue.js・Vite依存管理とビルド | Node.js 24、`tools`プロファイル |

ソース配置と残る移行事項は[ディレクトリ構成](102_directory-structure.md)を参照する。

## 前提

- Docker Desktopが起動していること
- Docker Compose v2以降を使用できること
- Webの`8080`、MySQLの`3306`等、設定したポートが空いていること

## 初回準備

PowerShellでリポジトリ直下へ移動し、ローカル設定を作成する。

```powershell
Copy-Item .env.example .env
docker compose run --rm app composer install
docker compose --profile tools run --rm --no-deps node npm ci
```

`.env`はGit管理外とし、本番の認証情報を記載しない。

## 基本操作

```powershell
# WebとMySQLを起動
docker compose up -d --build

# 状態を確認
docker compose ps

# ログを確認
docker compose logs -f app db

# CakePHPコマンド
docker compose exec app bin/cake

# マイグレーション状態
docker compose exec app bin/cake migrations status

# マイグレーション適用
docker compose exec app bin/cake migrations migrate

# 停止
docker compose down
```

CakePHPは`http://localhost:8080`で確認する。`.env`の`APP_PORT`を変更した場合はそのポートを使用する。

## フロントエンド（Vue.js）

`frontend/`はAPIと別に依存関係を管理し、利用者区分ごとに独立したViteエントリーを持つ。現在は`operator`と`admin`を実装済みである。

```powershell
# nodeサービスを起動
docker compose --profile tools up -d node

# 運営者FRONTを起動（Composeの公開ポートに合わせる）
docker compose --profile tools exec node npm run dev:operator -- --host 0.0.0.0 --port 5173

# 管理者FRONTをビルド
docker compose --profile tools exec node npm run build:admin
```

ホストのNode.jsを使用する場合は、`frontend/`で`npm ci`後に`npm run dev:operator`または`npm run dev:admin`を実行する。現在のVite設定の既定ポートは`5174`である。Compose経由では公開設定に合わせて`5173`を明示する。2つのFRONTを同時に起動する場合は、一方へ別ポートを指定する。

FRONTは`frontend/src/api/client.js`からCookieベースのセッションを使用してAPIを呼び出す。FRONTのポートを変更する場合は、`.env`の`FRONTEND_PORT`、APIのCORS許可オリジンおよびメールリンクの遷移先を一致させる。

ビルド成果物は`frontend/dist/operator/`または`frontend/dist/admin/`へ出力する。`app/resources/js/front/`は移行期間中の既存エントリーとして残っているが、新しい利用者別FRONTは`frontend/`へ追加する。

## テスト用アカウント

開発環境では、確認済みの運営者・管理者アカウントをコマンドで作成できる。本番では使用しない。

```powershell
# 運営者アカウント
docker compose exec -e OPERATOR_BOOTSTRAP_PASSWORD='パスワード' app bin/cake create_operator --email=operator@example.com

# 管理者アカウント
docker compose exec -e ADMIN_BOOTSTRAP_PASSWORD='パスワード' app bin/cake create_admin --email=admin@example.com --name="管理者"
```

`composer test`と`composer check`は現在、開発用データベースと同じデータベースを使用するため、実行すると開発データが削除される。必要なデータを保持したまま実行しない。

## テスト

```powershell
# PHPテスト・規約・静的解析
docker compose exec app composer check
```

テスト用データベースの分離状況を確認せずに、本番または保持が必要な開発データへテストを実行しない。

## ローカルデータベース接続

| 項目 | 値 |
|---|---|
| ホスト | `127.0.0.1` |
| ポート | `.env`の`MYSQL_PORT`（初期値`3306`） |
| データベース | `.env`の`MYSQL_DATABASE` |
| ユーザー | `.env`の`MYSQL_USER` |
| パスワード | `.env`の`MYSQL_PASSWORD` |

コンテナ間ではCakePHPからホスト名`db`、ポート`3306`で接続する。MySQLデータはDockerの名前付きボリューム`mysql-data`へ保持する。

`docker compose down`ではデータを削除しない。`docker compose down -v`はローカルデータを削除するため、意図して初期化する場合だけ使用する。

## データベース変更

- 変更は`app/config/Migrations/`のCakePHPマイグレーションで管理する。
- 適用済みマイグレーションを書き換えず、新しいマイグレーションを追加する。
- 詳細は[データベース](201_database.md)を参照する。

## 秘密情報

- 本番データベース、SMTP、SSH等の認証情報を`.env.example`へ記載しない。
- 本番用設定をローカルDocker環境へ流用しない。
- `.env`、CakePHPの環境別設定、鍵ファイルをGitへコミットしない。
