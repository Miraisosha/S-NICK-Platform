# Squash Platform Requirements v1.0

## 位置づけ

このディレクトリを、Squash Platform（日本語表記：スカッシュ　プラットフォーム）の要件・設計に関する正本（Single Source of Truth）とする。
現時点で未確定の内容は推測で確定せず、`検討中` と明記する。

## 既存設計資料との関係

旧称「S-NICK Platform」で作成された次の資料を、プロジェクト全体の方針・基本設計として参照する。

- [プロジェクト概要](../01_project-overview.md)
- [システム構成](../02_system-architecture.md)
- [サービス・業務領域計画](../03_service-domain-plan.md)
- [ライブ配信](../04_live-streaming.md)
- [ネットワーク・機材](../05_network-and-hardware.md)
- [開発ロードマップ](../06_development-roadmap.md)

役割分担は次のとおりとする。

| 資料 | 役割 |
|---|---|
| `docs/01`～`06` | 目的、全体構成、業務領域、配信・機材、ロードマップ |
| `docs/specifications/` | 利用者、機能、画面、イベント、業務ルール、状態遷移 |

両者に矛盾が見つかった場合は、勝手に一方へ合わせず、決定の経緯と影響範囲を確認してから修正する。

## 設計階層

要件は次の階層で整理する。

1. 利用者（Chapter）
2. 機能（Function）
3. 画面（Screen）
4. イベント（Event）
5. 業務ルール（Business Rule）

## ドキュメント一覧

| ファイル | 内容 |
|---|---|
| [FunctionalIndex.md](FunctionalIndex.md) | 利用者別機能とフェーズ1～4のリリース範囲 |
| [ScreenFlow.md](ScreenFlow.md) | 利用者別の入口と主要な画面遷移図 |
| [DataModel.md](DataModel.md) | 大会・試合・マーカー・固定QRコードの概念ER設計 |
| [ClassDesign.md](ClassDesign.md) | 大会運営・スケジュール・試合進行の概念クラス図と責務分担 |
| [UiDesignGuide.md](UiDesignGuide.md) | Squash Platform共通の配色、文字、ボタン、角丸、余白、キャラクター使用方針 |
| [000_SystemOverview.md](000_SystemOverview.md) | プロジェクトとシステムの概要 |
| [010_SystemArchitecture.md](010_SystemArchitecture.md) | システム構成・技術構成 |
| [020_UserRoles.md](020_UserRoles.md) | 利用者と権限 |
| [100_Public.md](100_Public.md) | パブリック向け機能 |
| [200_Operator.md](200_Operator.md) | 運営者向け機能 |
| [300_Marker.md](300_Marker.md) | マーカー向け機能 |
| [400_Player.md](400_Player.md) | 選手向け機能 |
| [500_Admin.md](500_Admin.md) | 管理者向け機能 |
| [600_CommonPlatform.md](600_CommonPlatform.md) | 共通基盤 |
| [900_FuturePlan.md](900_FuturePlan.md) | 将来構想 |
| [glossary.md](glossary.md) | 用語集 |
| [yaml/requirements.yaml](yaml/requirements.yaml) | 要件構造の機械可読表現 |
| [yaml/public.yaml](yaml/public.yaml) | パブリックダッシュボード・公開画面の機械可読表現 |
| [yaml/marker.yaml](yaml/marker.yaml) | マーカー機能の機械可読表現 |
| [yaml/live-streaming.yaml](yaml/live-streaming.yaml) | YouTube Live・OBS・コート別配信の機械可読表現 |
| [yaml/state-machine.yaml](yaml/state-machine.yaml) | 試合状態遷移 |

## 更新フロー

相談 → Requirements更新 → レビュー → コミット → 実装 → 動作確認、の順で進める。
