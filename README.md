# S-NICK Platform

S-NICK Platform は、スカッシュ大会の企画、エントリー受付、試合運営、スコア管理、ライブ配信、結果公開までを一元的に支援する統合プラットフォームです。

現在は仕様検討段階です。初期目標として、タイトル運営の基本機能と2コートの同時ライブ配信・リアルタイム得点表示を目指します。

## 目指すサービス

- ユーザー登録、ログイン、アカウント復旧
- タイトル、カテゴリ、エントリー、選手の管理
- ドロー作成、試合進行、コート管理
- マーカーによるスコア入力
- 観客向けの大型モニター・タブレット表示
- OBSとYouTube Liveを利用したライブ配信
- 大会ロゴ・スポンサー表示
- スタッフ、ランキング、結果公開

## 技術構成

| 区分 | 採用技術・サービス |
|---|---|
| 本番環境 | お名前.com レンタルサーバー RSプラン |
| バックエンド | PHP 8.5 / CakePHP |
| フロントエンド | Vue.js / Bootstrap |
| データベース | MySQL |
| ソース管理 | GitHub |
| サーバー接続 | SSH（接続確認済み） |

CakePHP、Vue.js、Bootstrap、MySQLの詳細バージョンと開発・デプロイ方法は検討中です。

## ドメイン構成

| URL | 役割 |
|---|---|
| `platform.s-nick.com` | 大会運営システム |
| `entry.s-nick.com` | エントリー受付 |
| `live.s-nick.com` | ライブ配信・観戦 |
| `ranking.s-nick.com` | ランキング |
| `insurance.s-nick.com` | 保険申込 |
| `api.s-nick.com` | API |

## ドキュメント

- [プロジェクト概要](docs/01_project-overview.md)
- [システム構成](docs/02_system-architecture.md)
- [サービス・業務領域計画](docs/03_service-domain-plan.md)
- [ライブ配信](docs/04_live-streaming.md)
- [ネットワーク・機材](docs/05_network-and-hardware.md)
- [開発ロードマップ](docs/06_development-roadmap.md)

## 現在の方針

- 決定済み事項と検討中事項を区別して記録します。
- 現段階では実装コードを作成しません。
- 仕様確定後、小さな機能単位で設計・実装・テストを進めます。
