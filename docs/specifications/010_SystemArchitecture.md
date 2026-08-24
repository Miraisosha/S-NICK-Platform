# 010 システムアーキテクチャ

## 技術構成

| 区分 | 採用候補・決定内容 |
|---|---|
| バックエンド | PHP 8.5、CakePHP 5.4 |
| フロントエンド | Vue.js 3.5、Bootstrap 5.3、Vite 8 |
| データベース | MySQL |
| ソース管理 | GitHub |
| 本番環境 | お名前.com レンタルサーバー RSプラン |
| リアルタイム連携 | WebSocket（JSON）を基本とし、切断・利用不可時はHTTPS APIポーリングへ自動切替 |
| 配信 | OBS、YouTube Live |

### ローカル開発環境

Docker Composeを使用し、PHP 8.5＋Apache、MySQL、Node.jsの開発環境を分離して起動する。本番のお名前.com レンタルサーバー RSプランへDockerコンテナを配置することは前提としない。

- MySQL 8.4 LTSをローカル開発用の暫定バージョンとする（2026-08-18時点）。本番MySQLのバージョン確認後に互換性を再評価する。
- Node.js 24をローカル開発用バージョンとする（2026-08-18時点）。
- ローカルの認証情報はGit管理外の`.env`に保持し、本番の認証情報を流用しない。
- 詳細な構築・操作手順は[ローカル開発環境](../07_local-development.md)を参照する。

### 本番デプロイ

`main`へのマージ後、GitHub ActionsでPHPテスト、コーディング規約確認、Vue.js・BootstrapのViteビルドおよび本番用Composer依存関係の構築を行い、成功した成果物をSSH・rsyncでお名前.com RSプランへ同期する。

- SSH認証情報と実際の配置パスはGitHubの`production` Environmentに保存し、Gitへ記録しない。
- 本番の`config/app_local.php`、ログ、一時ファイルおよびアップロードファイルを同期・削除対象から除外する。
- 配置先の目印ファイルと絶対パスを検証し、誤ったディレクトリへの同期・削除を防止する。
- 本番マイグレーションはバックアップ・復旧手順の確定まで無効とする。
- 詳細は[GitHub Actionsによる本番デプロイ](../08_deployment.md)を参照する。

単一のCakePHP APIバックエンドと単一のMySQLデータベースを使用する。利用者コードはURL、画面、権限を分けるためのものであり、利用者ごとに独立したアプリケーションや試合状態を作らない。Vue.jsの共通部品と共通APIクライアントを利用者別画面から使用する。

### ディレクトリとAPI・FRONTの分離

**決定済み（2026年8月24日改訂）**

以前は「FRONTとAPIを同一CakePHPアプリケーションとして配備し、別デプロイ単位への分割は行わない」としていたが、これは実際の意図と異なっていたため訂正する（2026年8月22日改訂）。FRONTとAPIは同一リポジトリで管理しつつ、ソース・依存関係・ビルド・配備は分離する。

さらに、FRONTは単一のVue SPAではなく、利用者区分ごとに完全に独立したViteエントリー（＝別々のアプリ）へ分割する（2026年8月24日改訂）。各アプリは本番でサブドメインごとに独立配信することを前提とする。

- ブラウザ画面を`FRONT`、JSONを入出力するバックエンドインターフェースを`API`と呼ぶ。
- **API**: `app/`にCakePHP 5の標準ディレクトリ構成でプロジェクトを配置する。CakePHPは`/api/v1/...`のJSON APIのみを提供し、HTML画面は描画しない（CakePHP自身の診断・エラーページを除く）。URLへバージョンを含め、API Controllerは`App\Controller\Api\V1`名前空間、`app/src/Controller/Api/V1`へ配置する。APIはCakePHPの自動フォールバックルートへ依存せず、エンドポイント実装時にHTTPメソッドを含むルートを明示する。API Controllerのテストは、本体と対応する`app/tests/TestCase/Controller/Api/V1`へ配置する。
- **FRONT**: リポジトリ直下`frontend/`に、Node.js/Viteで独立したVue.jsプロジェクトを配置する（`app/`のComposer依存とは別管理）。
  - 利用者区分ごとのアプリを`frontend/src/apps/{app}/`へ分ける（`home`＝公開ランディング、`operator`＝運営者管理画面、`entry`＝選手向けエントリー・登録、`player`＝選手向け画面、`marker`＝マーカー画面、`live`＝配信・OBSオーバーレイ、`display`＝観客表示画面、`admin`＝プラットフォーム管理者画面）。現時点で実装済みなのは`operator`と`admin`のみで、他は今後の実装時に同じ形で追加する。
  - 各アプリは`views/`（画面）・`components/`（アプリ固有部品）・`router/routes.js`（そのアプリのURLと画面の対応）・`main.js`（Vueアプリのエントリーポイント）を持つ。
  - 複数アプリで使うUI部品は`frontend/src/components/common/`、APIクライアントは`frontend/src/api/`、Piniaストアは`frontend/src/stores/`、Vue Composablesは`frontend/src/composables/`、汎用ユーティリティは`frontend/src/utils/`、画像・CSS等は`frontend/src/assets/`へ配置する（すべてのアプリから共有）。
  - ルーティングの共通処理（`createRouter()`の初期化、ログイン等の共通ガード、スクロール制御）は`frontend/src/router/`（`createAppRouter.js`・`guards/`・`scrollBehavior.js`）に置き、各アプリの`main.js`がそれを使って自分のルートを組み立てる。個別のURL・画面の対応（どのアプリがどの`/path`を持つか）は各アプリの`router/routes.js`が持つ。
  - 各アプリのHTMLエントリーは`frontend/entries/{app}/index.html`に置く（`frontend/src/apps/{app}/main.js`を読み込む）。ビルドは`vite build --mode {app}`のようにアプリ単位で実行し、成果物は`frontend/dist/{app}/`へ出力する（`frontend/package.json`の`build:operator`・`build:admin`等）。開発サーバーも同様に`vite --mode {app}`（`npm run dev:operator`等）で起動する。
  - 各アプリ内部の画面遷移はVue RouterによるSPAとする。アプリ間の遷移（例: 運営者画面から管理者画面へ）は別ビルド・別配信のため通常のリンク遷移（フルリロード）になる。
- **配備**: 本番は`platform.s-nick.com`（`home`等の公開FRONT）・`api.s-nick.com`（API、CakePHP）に加え、運営者・管理者等の各アプリも独立したサブドメイン（例: 案として`operator.platform.s-nick.com`、`admin.platform.s-nick.com`。確定次第この節を更新する）で配信する想定。いずれも同じ登録可能ドメイン（`s-nick.com`）のサブドメイン同士のため、セッションCookieの`Domain`属性を`.s-nick.com`に設定すれば`SameSite=Lax`のまま共有できる。APIはCORSで許可オリジンを明示的に絞り込む。

```text
（リポジトリ直下）
├─ app/                        # API（CakePHP）
│  ├─ config/
│  │  └─ routes.php
│  ├─ src/
│  │  ├─ Controller/
│  │  │  └─ Api/V1/            # JSON API Controller
│  │  ├─ Model/                # CakePHP Entity・Table
│  │  └─ ...                   # その他のCakePHP標準配置
│  ├─ templates/                # CakePHP自身の診断・エラーページのみ
│  └─ tests/TestCase/Controller/
│     └─ Api/V1/               # API Controllerテスト
└─ frontend/                    # FRONT（Vue.js、Node.js/Viteで独立ビルド）
   ├─ package.json
   ├─ vite.config.js
   ├─ entries/                  # アプリごとのHTMLエントリー
   │  ├─ operator/index.html
   │  └─ admin/index.html       # （home/entry/player/marker/live/displayは未実装）
   └─ src/
      ├─ apps/                  # 利用者区分ごとに独立したアプリ
      │  ├─ operator/
      │  │  ├─ main.js
      │  │  ├─ router/routes.js
      │  │  ├─ views/
      │  │  └─ components/
      │  └─ admin/
      │     ├─ main.js
      │     ├─ router/routes.js
      │     ├─ views/
      │     └─ components/
      ├─ router/                # 全アプリ共通のルーティング基盤
      │  ├─ createAppRouter.js
      │  ├─ guards/
      │  └─ scrollBehavior.js
      ├─ components/common/     # 複数アプリで使う共有UI部品
      ├─ api/                   # 共有APIクライアント
      ├─ stores/                # 共有Piniaストア
      ├─ composables/           # 共有Vue Composables
      ├─ utils/                 # 共有ユーティリティ
      └─ assets/                # 共有CSS・画像
```

初期段階でも別リポジトリへの分割は行わない（同一リポジトリで管理する）が、ソース・ビルド・配備単位はAPIと各FRONTアプリで分離する。

### 本番データベース接続

| 項目 | 設定 |
|---|---|
| データベース種別 | MySQL |
| ホスト | `mysql18.onamae.ne.jp` |
| データベース名 | `492th_snick_platform` |
| ユーザー名 | `492th_snick_platform` |
| 文字コード | `utf8mb4` |
| パスワード | 環境変数またはGit管理外の環境別設定から取得 |

- CakePHPの本番接続情報は環境別設定として管理する。
- データベースパスワードは設計書、通常の設定ファイル、ログ、Gitへ記載しない。
- 開発・検証・本番で接続情報を分離する。
- MySQLへの接続方式、TLS対応、ポート、照合順序は接続試験後に確定する。

## 論理構成

```mermaid
flowchart LR
    User["利用者ブラウザ"] --> Web["CakePHP / Vue.js"]
    Marker["マーカー端末"] --> Web
    Web --> DB[(MySQL)]
    Web --> Realtime["WebSocket配信基盤"]
    Realtime --> Display["大型表示"]
    Realtime --> Overlay["OBS Browser Source"]
    OBS["OBS PC"] --> YouTube["YouTube Live"]
    Overlay --> OBS
```

## 設計方針

- URL、利用者ロール、仕様書の章、画面構成を対応させる。
- 運営者、スタッフ、マーカー、選手等の通常ユーザーは、同一人物が大会ごとに異なる役割を持てる共通アカウント方式とする。プラットフォーム管理者は通常ユーザーとは別の管理者テーブル、認証ガード、ログイン画面およびセッションで管理する。
- スコア変更は履歴を保持し、訂正可能かつ監査可能にする。
- 外部公開と運営操作を権限で分離する。
- マーカー端末は必要なテーブルセットと操作履歴をブラウザ内へ永続保存し、通信断中も操作を中断させない。
- 同じ端末・ブラウザでは、ブラウザを閉じても最後に保存した状態から直ちに再開できるようにする。
- 未同期の操作は通信復旧後に順序を維持して再送し、二重登録と複数端末競合をサーバー側で検出する。
- 得点・判定等の更新要求はHTTPS APIへJSONで送信し、MySQLへ保存した結果を正式状態とする。
- WebSocketは保存済み状態の更新通知に使用し、通知メッセージはJSON形式とする。通知には少なくともイベント種別、対象ID、状態バージョンおよび発生日時を含める。
- WebSocketの切断時または利用不可時はHTTPS APIポーリングへ自動的に切り替え、再接続時に最新状態をAPIから再取得する。
- 本番では`wss://`を使用する。採用ライブラリ、常駐プロセスまたは外部配信基盤と、お名前.com RSプランでの実行可否は基本設計時の接続試験で確定する。

主要な業務処理の責務、依存関係およびCakePHPへの配置候補は、[概念クラス設計](ClassDesign.md)を参照する。現在は要件整理段階のため、CakePHPの`Table`、`Entity`およびControllerをテーブル単位で定義せず、Application Service、Policy、保存境界および共通サービスの責務を先に整理する。
