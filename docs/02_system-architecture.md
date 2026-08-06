# システム構成

## 1. 決定済みの技術構成

| 区分 | 採用内容 |
|---|---|
| 本番ホスティング | お名前.com レンタルサーバー RSプラン |
| サーバー接続 | SSH（接続確認済み） |
| バックエンド | PHP 8.5 / CakePHP |
| フロントエンド | Vue.js / Bootstrap |
| データベース | MySQL |
| 地図・位置情報 | Google Maps Platform Map Tiles API／Geocoding APIまたはPlaces API／OpenLayers |
| ソース管理 | GitHub |
| リポジトリ | `Miraisosha/S-NICK-Platform` |

CakePHP、Vue.js、Bootstrap、MySQL、Node.jsなどの詳細バージョンは検討中です。

Google Maps PlatformのMap Tiles APIをGoogle背景地図として使用し、画面の描画・操作にはOpenLayersを使用します。住所検索・座標取得にはGeocoding APIまたはPlaces APIを使用し、結果を同じGoogle背景地図上へ表示します。Googleロゴとデータ帰属表示、キャッシュ制限、APIキー制限等のポリシーを遵守し、実装前に料金と最新の利用条件を再確認します。

## 2. 論理構成

初期構成は、CakePHPバックエンド1つ、MySQLデータベース1つからなる単一アプリケーションとします。`public`、`operator`、`marker`、`player`、`admin`は利用者別のURL・画面・権限区分であり、別々の業務データや試合状態を持つ独立システムではありません。

```mermaid
flowchart LR
    subgraph Clients["利用端末"]
        Web["PC・スマートフォン"]
        Tablet["マーカー・観客用タブレット"]
        Monitor["大型モニター"]
        OBS["OBS Browser Source"]
    end

    Web --> Frontend["Vue.js + Bootstrap"]
    Tablet --> Frontend
    Monitor --> Frontend
    OBS --> Frontend
    Frontend -->|HTTPS / API| Backend["CakePHP / PHP 8.5"]
    Backend --> Database[("MySQL")]
```

## 3. 画面の用途

同じ試合データを、用途別の画面で表示します。

| 画面 | 主な利用者 | 特徴 |
|---|---|---|
| 管理画面 | 運営・スタッフ | イベント、カテゴリ、選手、試合、コート等の設定 |
| マーカー画面 | マーカー担当者 | タップしやすい得点入力 |
| 観客表示画面 | 観客 | タブレット・大型モニター向け全画面表示 |
| OBSオーバーレイ | 配信担当者 | 背景透過、映像上に重ねる情報だけを表示 |
| 公開画面 | 選手・観客 | エントリー、ドロー、結果、ライブ情報 |

## 4. リアルタイム更新

得点入力から表示までの候補は次のとおりです。採用方式は、お名前.com RSプランの常駐プロセス等の制約を確認して決定します。

1. WebSocket
2. Server-Sent Events
3. 1秒程度のAPIポーリング
4. 外部リアルタイムサービス

```mermaid
sequenceDiagram
    participant M as マーカー画面
    participant A as CakePHP API
    participant D as MySQL
    participant V as 観客表示
    participant O as OBSオーバーレイ

    M->>A: 得点更新
    A->>D: 得点を保存
    A-->>V: 更新を通知または取得
    A-->>O: 更新を通知または取得
```

マーカーは完全オフライン対応とし、事前取得した試合、選手、競技設定と確定状態を端末へ保持します。得点等の操作は端末内へ永続保存し、通信復旧後に順序を維持して同期します。ブラウザを閉じても、同じ端末・ブラウザで直前状態から再開できるようにします。具体的なリアルタイム更新方式、オフライン競合制御、許容遅延は基本設計で確定します。

## 5. 本番配置

- 本番URLは `platform.s-nick.com` を使用します。
- SSH接続は、既存のS-NICKイベント保険加入フォームで確認済みの方法を参考にします。
- SSH鍵、ユーザー名、パス、データベース接続情報などの秘密情報はリポジトリへ保存しません。
- Vue.jsのビルド場所、CakePHPとの統合方法、GitHubから本番へのデプロイ方法は検討中です。
- 開発・検証・本番環境の分離方法は検討中です。

## 6. 非機能要件として検討する事項

- 認証と権限管理
- 個人情報・パスワードの保護
- バックアップと復旧（紙記録の一括登録と指定時点復旧の高度化は将来導入）
- 操作履歴・監査ログ
- 同時アクセス数と性能
- 障害監視
- アクセシビリティ
- タブレット・大型モニターへのレスポンシブ対応
- OBS Browser Sourceでの安定動作
