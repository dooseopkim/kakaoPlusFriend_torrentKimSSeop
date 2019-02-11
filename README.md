# kakaoPlusFriend_torrentKimSSeop

카카오 플러스 친구로 소장중인 영화 및 드라마를 스트리밍 하는 서비스입니다.  
> 현재는 저작권에 문제로 서비스하지 않습니다.

카카오 플러스 친구는 채팅 서비스로 메뉴형, 스마트 API 두 방식을 제공하다가 2018년 12월 31일 이후 신규 가입을 받지 않습니다.  
채팅 서비스 이외에 챗봇 서비스를 추가한 새로운 서비스를 런칭될 것으로 보이며 2019년 12월 31일 이후에는 기존에 스마트 API방식 채팅 서비스를  
공식적으로 종료한다고 합니다.

[카카오플러스친구 Github](https://github.com/plusfriend/auto_reply) 에서 자세한 사항을 확인 할 수 있습니다.

***
## 개발환경
Cafe24에 APM 환경을구축하였고, 추가적으로 영화 및 컨텐츠 정보를 수집하기 위해 Python으로 크롤링을 하였습니다.  
Apache웹서버, PHP7.0, MariaDB-10.0.x, Python3.6.2  
  
    
***

### Tip!
카카오 스마트API 채팅은 keybord, message 두 개의 파일? 로 이루어집니다.  
두 파일은 확장자가 .php, .jsp, .html 등이 붙어 있으면 오류가 납니다.
https://(url)/message 로 요청을 보냈을 때, https://(url)/message.php 로 보내지도록 해야합니다.  
저는 .htaccess 파일을 수정하여 해결했습니다.  
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([^\.]+)$ $1.php [NC,L]
```
이후에 확장자 문제를 해결할 수 있었습니다.  
.  
.  
.  
.  


keybord는 플러스 친구와 채팅을 시작하면 처음으로 사용자에게 보여지는 화면입니다.  

> 다음과 같은 화면입니다.  

![kakaotalk_20190211_145704732](https://user-images.githubusercontent.com/34496143/52547863-b48b6e80-2e0d-11e9-91ac-23fd8735f32f.jpg)

이후에는 message.php로 모든 요청과 응답을 처리합니다.  
요청에는 user_key, type, content 3개의 파라미터 값이 있으며, 사용자가 누른 버튼의 text값은 content가 됩니다.  

> 다음과 같은 화면에서 사용자가 '컨텐츠 시청하기'버튼을 클릭하면 message.php로 content값이 '컨텐츠 시청하기' 가 들어옵니다.  
> content 값 하나만으로 모든 상황을 핸들링 해야하는지라 흐름 설계가 꽤 난해합니다.  

![kakaotalk_20190211_150218884](https://user-images.githubusercontent.com/34496143/52548276-0b924300-2e10-11e9-8386-89b06b4fcb19.jpg)
.  
.  
.  
.  

## 이후에..
애초에 계획은 서버쪽에 IBM 왓슨을 연동해볼 계획이였으나 곧 서비스개편이 있다고 하니 기다렸다가 작업해볼 예정입니다..  
왓슨과 카카오톡을 연동한 예제는 [여기..](https://developer.ibm.com/kr/watson/2017/01/13/watsonchatbot-1-watson-conversation/) 를 방문해보시면 더 많은 정보를 얻을 수 있습니다.
