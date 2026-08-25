# ローカル開発環境

## 位置づけ

本書は、Squash Platformのローカル開発環境と、現在実行できる操作を管理する。本番環境はお名前.com レンタルサーバー RSプランであり、Dockerコンテナをそのまま本番へ配置しない。

## 現在の構成

2026年8月25日時点では、CakePHPとVue.jsの既存ソースはいずれも`app/`配下にある。目標とする`frontend/`分離は未実施であり、本書では実際に存在しないディレクトリやnpmスクリプトを実行手順として扱わない。

| サービス | 用途 | バージョン・状態 |
|---|---|---|
| `app` | Apache、PHP、Composer、CakePHP | PHP 8.5 / CakePHP 5.4 |
| `db` | ローカルデータベース | MySQL 8.4 LTS（2026年8月18日時点の暫定版） |
| `node` | `app/`のVue.js・Vite依存管理とビルド | Node.js 24、`tools`プロファイル |

目標ディレクトリと移行時の確認事項は[ディレクトリ構成](102_directory-structure.md)を参照する。

## 前提

- Docker Desktopが起動していること
- Docker Compose v2以降を使用できること
- Webの`8080`、MySQLの`3306`等、設定したポートが空いていること

## 初回準備

PowerShellでリポジトリ直下へ移動し、ローカル設定を作成する。

```powershell
Copy-Item .env.example .env
docker compose run --rm app composer install
docker compose --profile tools run --rm --no-deps node npm install
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

## 現在のFRONTビルド

現在のVue.jsソースは`app/resources/js/front/`、npm設定は`app/package.json`にある。

```powershell
# 1回ビルド
docker compose --profile tools run --rm --no-deps node npm run build

# 監視ビルド
docker compose --profile tools up -d node
docker compose --profile tools exec node npm run dev
```

成果物は現在のVite設定に従って`app/webroot/build/`へ出力される。

## FRONT分離後の予定

`frontend/`への移行後は、nodeサービスのマウント先、npmキャッシュ、Vite開発サーバー、アプリ別ポート、CORS許可オリジンおよびビルドコマンドを更新する。移行完了前に`cd frontend`や`npm run dev:operator`等を標準手順にはしない。

## テスト

```powershell
# PHPテスト・規約・静的解析
docker compose exec app composer check
```

テスト用データベースの分離状況を確認せずに、本番または保持が必要な開発データへテストを実行しない。テスト用アカウントの作成方法は、対応するコマンドが実装された時点で追記する。

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
