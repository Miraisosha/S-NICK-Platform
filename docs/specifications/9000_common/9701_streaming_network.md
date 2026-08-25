# 配信・ネットワーク

# 670 配信・ネットワーク

- 映像系統は GoPro → HDMI → ワイヤレスHDMI → キャプチャ → OBS → YouTube Live を基本候補とする。
- OBS PCは有線LAN、表示タブレットはWi-Fiを基本とする。
- LTE/5GルーターはデュアルSIMの切替型フェイルオーバーを基本候補とし、回線ボンディングとは区別する。
- コートごとにYouTube配信枠、OBS設定、公開視聴URL、オーバーレイURLを分離する。
- ストリームキーは秘密情報とし、公開用データ、ログ、Gitへ保存しない。
- 詳細は[ライブ配信](9702_live_streaming.md)と[サーバ・インフラ構成](../../architecture/103_infrastructure.md)を参照する。

