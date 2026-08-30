# ディレクトリ構成

## 位置づけ

本書は、APIとFRONTの責務、ソース配置、依存関係およびビルド境界を定義する。詳細な画面構成は[フロントエンド](301_frontend.md)、CakePHP内部の責務は[バックエンド](302_backend.md)、URL設計は[API](303_api.md)を参照する。


## ディレクトリ

```text
（リポジトリ直下）
├─ .github/
│  └─ workflows/
│     └─ deploy-production.yml      # 本番デプロイ
├─ app/                             # API・既存画面（CakePHP）
│  ├─ composer.json                 # PHP依存関係
│  ├─ composer.lock
│  ├─ package.json                  # 既存FRONTの依存関係
│  ├─ package-lock.json
│  ├─ vite.config.js                # 既存FRONTのビルド設定
│  ├─ config/
│  │  ├─ Migrations/                # データベースマイグレーション
│  │  └─ routes.php                 # HTML・JSON APIルート
│  ├─ resources/js/front/           # 移行期間中の既存Viteエントリー
│  ├─ src/
│  │  ├─ Command/                   # 運用・開発用CakePHPコマンド
│  │  ├─ Controller/
│  │  │  └─ Api/V1/                # JSON API Controller
│  │  │     └─ Admin/               # 管理者専用API
│  │  ├─ Mailer/                    # メール作成・送信
│  │  ├─ Middleware/                # CORS等のHTTP共通処理
│  │  ├─ Model/
│  │  │  ├─ Entity/                 # CakePHP Entity
│  │  │  └─ Table/                  # CakePHP Table
│  │  └─ Service/
│  │     ├─ Admin/                  # 管理者業務
│  │     ├─ Auth/                   # 認証・アカウント
│  │     └─ Event/                  # イベント業務
│  ├─ templates/                    # 既存HTML、メール、エラー画面
│  ├─ tests/TestCase/               # PHPテスト
│  └─ webroot/                      # CakePHP公開ディレクトリ
│     └─ build/                     # 既存FRONTのビルド成果物
├─ frontend/                        # 利用者別FRONT（Vue.js / Vite）
│  ├─ package.json                  # FRONT依存関係・アプリ別コマンド
│  ├─ package-lock.json
│  ├─ vite.config.js                # アプリ別開発・ビルド設定
│  ├─ entries/
│  │  ├─ operator/index.html        # 運営者FRONTのHTMLエントリー
│  │  └─ admin/index.html           # 管理者FRONTのHTMLエントリー
│  ├─ public/                       # ビルド時にそのまま配信する静的ファイル
│  └─ src/
│     ├─ api/                       # 共通APIクライアント
│     ├─ apps/
│     │  ├─ operator/               # 運営者アプリ
│     │  └─ admin/                  # 管理者アプリ
│     ├─ assets/                    # 共通CSS・画像
│     ├─ components/common/         # 共通UI部品
│     ├─ router/                    # 共通ルーター初期化・ガード
│     ├─ stores/                    # Piniaストア
│     └─ utils/                     # 共通ユーティリティ
├─ docker/php/                      # ローカル開発用PHP・Apache設定
├─ docs/                            # 設計・仕様資料
└─ compose.yaml                     # ローカル開発サービス定義
```

## FRONTアプリ

| アプリコード | 用途 | 本番ドメイン 
|---|---|---|
| `home` | 公開ランディング | `squash-platform.com` 
| `operator` | 運営者・スタッフ | `operator.squash-platform.com`
| `entry` | Webエントリー | `entry.squash-platform.com`
| `player` | 選手マイページ | `player.squash-platform.com`
| `marker` | マーカー | `marker.squash-platform.com`
| `live` | ライブ配信 | `live.squash-platform.com`
| `display` | 観客・大型表示 | `display.squash-platform.com`
| `admin` | プラットフォーム管理 | `admin.squash-platform.com` 

## 配置規約

### API

- CakePHPは`/api/v1/...`のJSON APIを提供する。
- API Controllerは`App\Controller\Api\V1`名前空間と`app/src/Controller/Api/V1/`へ配置する。
- HTTPメソッドを含むルートを`app/config/routes.php`へ明示する。
- API Controllerのテストは`app/tests/TestCase/Controller/Api/V1/`へ対応付ける。

### FRONT

- 各アプリは固有の`main.js`、ルート、画面、アプリ固有コンポーネントを持つ。
- 複数アプリで利用する処理だけを共通ディレクトリへ置き、アプリ固有の業務処理を安易に共有しない。
- アプリ内部はVue RouterによるSPAとし、アプリ間は通常のリンク遷移とする。
- ビルド成果物は`frontend/dist/{app}/`へアプリ別に出力する。

## 残る移行事項

- `app/resources/js/front/`とCakePHP HTML画面の維持・廃止範囲
- 未実装FRONTの追加順序とアプリコード
- GitHub Actions成果物のAPI・FRONT別配備先
- 各FRONTの本番ドキュメントルートとSPAフォールバック設定

既存画面を廃止する場合は、利用中の機能とデプロイへの影響を確認してから行う。
