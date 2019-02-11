<?php
	
	/**
	사용자 응답 받기 
	content, user_key
	**/
	$data = json_decode(file_get_contents('php://input'),true);
	$content = $data["content"];
	$kakao_key = $data["user_key"];

	//DB연결
	$conn = mysqli_connect("localhost","entjqcjswo","qhrhtlvdj0414*","entjqcjswo");

	//사용자 관련 전역 변수 
	$user_id;
	$reg_date;
	$last_come;
	$alias;
	$currstatus;
	$prevstatus;
	$user_level;

	//기존 회원 여부 확인
	$result = mysqli_query($conn, 'SELECT * FROM tbl_users WHERE kakao_key = \''.$kakao_key.'\'');
	$row = mysqli_fetch_array($result);
	#기존회원
	if($row){
		$user_id = $row['user_id'];
		$reg_date = $row['reg_date'];
		$last_come = $row['last_come'];
		$alias = $row['alias'];
		$currstatus = $row['curr_status'];
		$prevstatus = $row['prev_status'];
		$user_level = $row['user_level'];
		//마지막 접속시간 업데이트
		mysqli_query($conn, 'UPDATE tbl_users SET last_come = NOW() WHERE kakao_key = \''.$kakao_key.'\'');

	}
	#신규회원
	else{
		//회원가입 후, 가입정보 반환
		mysqli_query($conn, "INSERT INTO tbl_users VALUES((SELECT IFNULL(MAX(u.user_id),0)+1 FROM tbl_users u),\"".$kakao_key."\",now(),now(),'None','AccountReg','None','basic')");
		$result = mysqli_query($conn, 'SELECT * FROM tbl_users WHERE kakao_key = \''.$kakao_key.'\'');
		$row = mysqli_fetch_array($result);

		$user_id = $row['user_id'];
		$reg_date = $row['reg_date'];
		$last_come = $row['last_come'];
		$alias = $row['alias'];
		$currstatus = $row['curr_status'];
		$prevstatus = $row['prev_status'];
		$user_level = $row['user_level'];
	}

	#Home 키를 입력했을 때 currstatus를 Home_0으로 초기화시킴
	#시작하기 들어왔을때 별칭이 없는 회원은 별칭등록 화면으로 이동
	if($content=='Home'||$content=='시작하기'){
		global $currstatus;
		if($currstatus!='AccountReg'){
			updateStatus($currstatus,'Home_0');
			$currstatus = 'Home_0'; 			
		}

	}


	returnScreen($currstatus,"none");

	function returnScreen($status,$str){
		global $content,$conn,$user_id,$reg_date,$last_come,$alias,$prevstatus,$user_level;
		logging($user_id,$content,"");

		switch ($status) {
			case 'star':
				mysqli_query($conn, "INSERT INTO tbl_star VALUES((SELECT IFNULL(MAX(u.st_id),0)+1 FROM tbl_star u),\"".$content."\",now())");
				echo'{
						"message":
							{
								"text":"나도 사랑해 ~~~"
							},
						"keyboard":{
							"type":"buttons",
							"buttons":["Home"]
						}
				}';
			//회원가입 이후 별칭 업데이트
			case 'AccountReg':
				// mysqli_query($conn,"UPDATE tbl_users SET prev_status = 'AccountReg', curr_status = 'AliasReg' WHERE user_id = \"".$user_id."\"");
				updateStatus('AccountReg','AliasReg');
				echo'{
						"message":
							{
								"text":"오호홋! (뽀뽀)(뽀뽀)   \n신규회원이시네요, \n사용할 별칭을 입력해주세요"
							},
						"keyboard":
							{
								"type": "text"
							}
					}';
				break;
			//별칭 입력후 Home 이동		
			case 'AliasReg':
				$result = mysqli_query($conn,"SELECT * FROM tbl_users WHERE alias = \"".$content."\"");
				if(mysqli_num_rows($result)==1){
					echo'{
							"message":
								{
									"text":"옴마나! (허걱)(허걱)   \n======================\n▶ 중복된 값 \n  새로운 별칭을 입력해주세요."
								},
							"keyboard":
								{
									"type": "text"
								}
						}';
				}else{
					// mysqli_query($conn,"UPDATE tbl_users SET prev_status = 'AliasReg', curr_status = 'Home_0' WHERE user_id = \"".$user_id."\"");
					updateStatus('AliasReg','Home_0');
					mysqli_query($conn,"UPDATE tbl_users SET alias = \"".$content."\" WHERE user_id = \"".$user_id."\"");			
					echo'{
							"message":
								{
									"text":"이제 \"'.$content.'\" 님 이라고 부를게요~ (부끄) \n======================\n하단에 버튼을 선택하여 이용해주세요!\n아! 도움말을 꼭 읽어주세요!!"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["컨텐츠 시청하기","이어보기","Home","도움말","문의하기","컨텐츠 신청하기"]
								}
						}';

				}
				break;

			//========================================================================================================================================
			// Level-0 MAIN 화면(Home)
			//========================================================================================================================================
			case 'Home_0':
				switch ($content) {
					case '컨텐츠 시청하기':
						updateStatus('Home_0','watching');
						echo '{"message":{"text":" ⎛° ͜ʖ°⎞⎠ \n【# 컨텐츠 시청하기】\n======================\n▶ 국내영화\n▶ 해외영화\n▶ 장르별영화\n▶ 국내드라마\n▶ 국내예능\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "},"keyboard":{"type": "buttons","buttons":["국내영화","해외영화","장르별영화","국내드라마","국내예능","Home","뒤로"]}}';	
						break;
					case '컨텐츠 신청하기':
						updateStatus('Home_0','requestContent');
						echo'{"message":{"text":" ⎛° ͜ʖ°⎞⎠ \n【# 컨텐츠 신청하기】\n======================\n보고싶은 컨텐츠를 입력하세요\n▶ 신청양식\n장르.제목.출연진  \nex)\n국내드라마.도깨비.공유 김고은\n======================\n▶ Home 화면으로 이동\n\"Home\" 입력 후 전송\n▶ 뒤로가기 \n\"뒤로\" 입력 후 전송"},"keyboard":{"type": "text"}}';	
						break;
					case '이어보기':
						updateStatus('Home_0','history');
						echo '{"message":{"text":" ⎛° ͜ʖ°⎞⎠ \n【# 이어보기】\n======================\n▶ 마지막 시청\n▶ 시청이력\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "},"keyboard":{"type": "buttons","buttons":["마지막 시청","시청이력","Home","뒤로"]}}';	
						break;
					// https://itunes.apple.com/kr/app/uc-browser/id1048518592?mt=8 <--AppStore
	                case '도움말':
	                // https://itunes.apple.com/kr/app/uc-browser/id1048518592?mt=8 <--AppStore
	                   echo'
	                   {
	                        "message":
	                           {
	                              "text":" ⎛° ͜ʖ°⎞⎠ \n【# 도움말】\n====================\n▶▶ 필독 ◀◀\n1. 하단 \"uc브라우저 다운\" 버튼 \n 클릭하여 \"uc브라우저\"를 꼭 설 치해주세요.\n (정신건강을 지키기위해..)\n====================\n2. 영상 재생 버튼을 클릭하면\n  카카오톡 내에 화면이 로딩됩니다. 느림..\n 화면 우측 상단부에 · 세 개 짜리 \n 메뉴 클릭하여 \"다른 브라우저로 열기\" \n uc브라우저 선택하면 됩니다.\n ▶ Android사용자는 버튼 선택\n-------------------------------------\n ▶ App Store는 아래 링크 클릭\nhttps://itunes.apple.com/kr/app/uc-browser/id1048518592?mt=8",
	                               "photo": 
	                                  {
	                                    "url": "http://106.10.52.95/image/kimMovieMan2.jpg",
	                                    "width": 203,
	                                    "height": 290
	                                  },
	                              "message_button": 
	                                 {
	                                    "label": "uc브라우저 다운",
	                                    "url": "https://play.google.com/store/apps/details?id=com.UCMobile.intl&hl=ko"
	                                 }
	                           },
	                        "keyboard":
	                           {
	                              "type": "buttons",
	                              "buttons":["컨텐츠 시청하기","이어보기","Home","도움말","문의하기","컨텐츠 신청하기"]
	                           }
	                     }';   
	                  break;
					case '문의하기':
						echo'{"message":{"text":" ⎛° ͜ʖ°⎞⎠ \n【# 문의하기】\n======================\n▶ 관리자 카카오톡 ID  \nentjqcjswo@naver.com \n카카오톡으로 문의해주세요\n메일도 가능해요"},"keyboard":{"type":"buttons","buttons":["컨텐츠 시청하기","이어보기","Home","도움말","문의하기","컨텐츠 신청하기"]}}';
						break;
					default:
						echo'
							{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n\"'.$alias.'\" 님 안녕하세요~\n======================\n마지막 접속\n'.$last_come.'\n======================\n첨 접속했거나, 어려울때는!!\n도움말을 꼭 읽어주세요!!  "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["컨텐츠 시청하기","이어보기","Home","도움말","문의하기","컨텐츠 신청하기"]
									}
							}';
						break;
				}
				break;

			//========================================================================================================================================
			// Level-1 컨텐츠 시청하기 화면
			//========================================================================================================================================
			case 'watching':
				switch ($content) {
					case '국내영화':
						updateStatus('watching','komv_watching');			
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
									}
							}';
						break;
					case '해외영화':
						updateStatus('watching','fgmv_watching');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 해외영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(ABC순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 ABC순 정렬\n  ☞ 전체목록(ABC)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["전체목록(최신순)","전체목록(ABC순)","이름으로검색","Home","뒤로"]
									}
							}';
						break;
					case '장르별영화':
						echo'{
								"message":{
									"text":"아직 준비중인 기능입니다. \n빠른 시일 내에 완성하겠습니다."
								},
								"keyboard":{
									"type":"buttons",
									"buttons":["Home"]
								}
						}';					
						// updateStatus('watching','genremv_watching');
						// echo'{
						// 		"message":{
						// 			"text":" ⎛° ͜ʖ°⎞⎠ \n【# 장르별영화】\n======================\n▶ 액션 \n▶ 로맨스 \n▶ 드라마 \n▶ 등등.. \n 보고싶은 장르를 선택해주세요"
						// 		},
						// 		"keyboard":{
						// 			"type":"buttons",
						// 			"buttons":["드라마","판타지","서부","공포","멜로/로맨스","모험","스릴러","느와르","컬트","다큐멘터리","코미디","가족","미스터리","전쟁","애니메이션","범죄","뮤지컬","SF","액션","무협","에로","서스펜스","서사","블랙코미디","실험","공연실황","Home","뒤로"]
						// 		}
						// }';
						break;
					case '국내드라마':
						updateStatus('watching','kodr_watching');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내드라마】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n드라마 제목으로 검색\n  ☞ 이름으로검색"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
									}
							}';
						break;
					case '국내예능':
						updateStatus('watching','koen_watching');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내예능】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n프로 제목으로 검색\n  ☞ 이름으로검색"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
									}
							}';
						break;
					case '뒤로':
						updateStatus('watching','Home_0');
						echo'
							{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n\"'.$alias.'\" 님 안녕하세요~\n======================\n마지막 접속\n'.$last_come.'\n======================\n첨 접속했거나, 어려울때는!!\n도움말을 꼭 읽어주세요!!  "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["컨텐츠 시청하기","이어보기","Home","도움말","문의하기","컨텐츠 신청하기"]
									}
							}';
						break;								
					default:
						echo '{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 컨텐츠 시청하기】\n======================\n▶ 국내영화\n▶ 해외영화\n▶ 국내드라마\n▶ 국내예능\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["국내영화","해외영화","장르별영화","국내드라마","국내예능","Home","뒤로"]
									}
								}';	
						break;
				}
				break;

			//========================================================================================================================================
			// Level-1 컨텐츠 신청하기 화면
			//========================================================================================================================================
			case 'requestContent':
				//국내드라마.도깨비.공유 김고은

				//양식에 맞는지 먼저 검사하고
				//양식에 맞지 않다면 다시 입력시킨다.
				//양식에 일치한다면									
				//request 테이블에 하나씩 누적시킨다.
				if($content=='뒤로'){
					updateStatus('requestContent','Home_0');
					echo'
						{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n\"'.$alias.'\" 님 안녕하세요~\n======================\n마지막 접속\n'.$last_come.'\n======================\n첨 접속했거나, 어려울때는!!\n도움말을 꼭 읽어주세요!!  "
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["컨텐츠 시청하기","이어보기","Home","도움말","문의하기","컨텐츠 신청하기"]
								}
						}';
				}else{
					$requestValues = explode('.',$content);
					if(count($requestValues)!=3){
						echo'{
								"message":
									{
										"text":" (ಢ‸ಢ) (ಢ‸ಢ)  \n【# 컨텐츠 신청하기】  『실패』\n======================\n신청양식을 확인해주세요 \n▶신청양식\n장르.제목.출연진  \nex)\n국내드라마.도깨비.공유 김고은"
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
					}else{
						$requestGenre = $requestValues[0];
						$requestTitle = $requestValues[1];
						$requestActors = $requestValues[2];
						$row = mysqli_query($conn,"INSERT INTO `tbl_request`(`request_id`, `request_date`, `request_userID`, `requestGenre`, `requestTitle`, `requestActors`, `upload_yn`) VALUES ((SELECT IFNULL(MAX(u.request_id),0)+1 FROM tbl_request u),NOW(),".$user_id.",\"".$requestGenre."\",\"".$requestTitle."\",\"".$requestActors."\",\"N\")");
						if(!$row){
							echo'{"message":{"text":" (ಢ‸ಢ) (ಢ‸ಢ)  \n【# 컨텐츠 신청하기】  『실패』\n======================\n신청양식을 확인해주세요 \n▶신청양식\n장르.제목.출연진  \nex)\n국내드라마.도깨비.공유 김고은"},"keyboard":{"type": "text"}}';	
						}else{
							echo'{"message":{ "text":" ⎛° ͜ʖ°⎞⎠  \n【# 컨텐츠 신청하기】  『성공』\n======================\n▶구분\n   '.$requestGenre.'\n▶제목\n   '.$requestTitle.'\n▶출연진\n   '.$requestActors.'\n======================\n신청이 완료되었습니다."},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
						}


					}	
				}
				break;

			//========================================================================================================================================
			// Level-1 컨텐츠 이어보기 화면
			//========================================================================================================================================
			case 'history':
				switch($content){
					case '마지막 시청':
						$recentHistory = mysqli_query($conn,"SELECT * FROM history WHERE user_id = \"".$user_id."\" ORDER BY view_date DESC LIMIT 1");
						if($recentHistoryRow = mysqli_fetch_array($recentHistory)){
							$history_id = $recentHistoryRow['history_id'];
							$view_date = $recentHistoryRow['view_date'];
							$tv_src_id = $recentHistoryRow['tv_src_id'];
							$user_id = $recentHistoryRow['user_id'];
							$mv_id = $recentHistoryRow['mv_id'];
							$hrcontent = $recentHistoryRow['contents'];


							if($tv_src_id!=NULL){
								// 예능인지 드라마인지 파악
								$tmp_tv_src_result = mysqli_query($conn,"SELECT * FROM tbl_tv_src src, tbl_tv_base base WHERE src.tv_id = base.tv_id AND src.tv_src_id=".$tv_src_id."");
								$tmp_tv_src_row = mysqli_fetch_array($tmp_tv_src_result);
								$tmp_tv_src_genre = $tmp_tv_src_row['genre'];

								if($tmp_tv_src_genre=='드라마'){
									updateStatus('history','kodr_watching_view_src');
								}else{
									updateStatus('history','koen_watching_view_src');
								}
							}else{
								// 국내영화인지 해외영화인지 파악
								$tmp_movie_result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_id = ".$mv_id."");
								$tmp_movie_row = mysqli_fetch_array($tmp_movie_result);
								$tmp_movie_country = $tmp_movie_row['country'];

								if($tmp_movie_country=='한국'){
									updateStatus('history','komv_watching_view');
								}else{
									updateStatus('history','fgmv_watching_view');
								}
							}
							echo '{
									"message":{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 마지막 시청이력】\n======================\n▶ 시청시간\n'.$view_date.'\n▶ 시청 컨텐츠\n'.$hrcontent.'"
									},
									"keyboard":{
										"type":"buttons",
										"buttons":["'.$hrcontent.'","Home","뒤로"]
									}
							}';
						}

						break;

					case '시청이력':
						$recentHistoryResult = mysqli_query($conn,"SELECT h1.history_id, h1.view_date, h1.tv_src_id, h1.user_id, h1.mv_id, h1.contents FROM history h1, (SELECT contents, MAX(view_date) AS max_view_date FROM history GROUP BY contents) AS h2 WHERE h1.contents= h2.contents AND h1.view_date = h2.max_view_date AND user_id = ".$user_id." ORDER BY h1.view_date DESC LIMIT 10");
						// if($recentHistoryRow = mysqli_fetch_array($recentHistoryResult)){
							$returnHistoryList = array();
							while($recentHistoryRow = mysqli_fetch_array($recentHistoryResult)){
								$history_id = $recentHistoryRow['history_id'];
								$view_date = $recentHistoryRow['view_date'];
								$tv_src_id = $recentHistoryRow['tv_src_id'];
								$user_id = $recentHistoryRow['user_id'];
								$mv_id = $recentHistoryRow['mv_id'];
								$hrcontent = $recentHistoryRow['contents'];

								if($tv_src_id!=NULL){
									// 예능인지 드라마인지 파악
									$tmp_tv_src_result = mysqli_query($conn,"SELECT * FROM tbl_tv_src src, tbl_tv_base base WHERE src.tv_id = base.tv_id AND src.tv_src_id=".$tv_src_id."");
									$tmp_tv_src_row = mysqli_fetch_array($tmp_tv_src_result);
									$tmp_tv_src_genre = $tmp_tv_src_row['genre'];

									if($tmp_tv_src_genre=='드라마'){
										array_push($returnHistoryList, '"국내드라마//'.$hrcontent.'"');
										// updateStatus('history','kodr_watching_view_src');
									}else{
										array_push($returnHistoryList, '"국내예능//'.$hrcontent.'"');
										// updateStatus('history','koen_watching_view_src');
									}
								}else{
									// 국내영화인지 해외영화인지 파악
									$tmp_movie_result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_id = ".$mv_id."");
									$tmp_movie_row = mysqli_fetch_array($tmp_movie_result);
									$tmp_movie_country = $tmp_movie_row['country'];

									if($tmp_movie_country=='한국'){
										array_push($returnHistoryList, '"국내영화//'.$hrcontent.'"');
										// updateStatus('history','komv_watching_view');
									}else{
										array_push($returnHistoryList, '"해외영화//'.$hrcontent.'"');
										// updateStatus('history','fgmv_watching_view');
									}
								}
							}
						array_push($returnHistoryList,'"Home"');
						array_push($returnHistoryList,'"뒤로"');
						$tmp = implode(',', $returnHistoryList);

						$total_history_count = mysqli_num_rows($recentHistoryResult);
						if($total_history_count<1){
							echo '{
									"message":{
										"text":"시청이력이 있어야 사용 가능합니다."
									},
									"keyboard":{
										"type":"buttons",
										"buttons":["Home","뒤로"]
									}
							}';
						}else{
							updateStatus('history','history_watching');
							echo'{
									"message":{
										"text":"최근 시청목록 '.$total_history_count.' 개를 보여드릴게요 "
									},
									"keyboard":{
										"type":"buttons",
										"buttons":['.$tmp.']
									}
							}';
						}

						break;

					case '뒤로':
						updateStatus('history','Home_0');
						echo'
							{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n\"'.$alias.'\" 님 안녕하세요~\n======================\n마지막 접속\n'.$last_come.'\n======================\n첨 접속했거나, 어려울때는!!\n도움말을 꼭 읽어주세요!!  "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["컨텐츠 시청하기","이어보기","Home","도움말","문의하기","컨텐츠 신청하기"]
									}
							}';
						break;		

					default:
						echo '{"message":{"text":" ⎛° ͜ʖ°⎞⎠ \n【# 이어보기】\n======================\n▶ 최근시청\n▶ 시청이력\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "},"keyboard":{"type": "buttons","buttons":["최근시청","시청이력","Home","뒤로"]}}';	
						break;							
				}
		
				break;
			//========================================================================================================================================
			// Level-1 이어보기 시청이력 화면(선택했을 때)
			//========================================================================================================================================	
			case 'history_watching':
				$tmp_contents = explode("//",$content);
				$tmp_categ = $tmp_contents[0];
				$tmp_titles = $tmp_contents[1];

				if($content=='뒤로'){
					updateStatus('history_watching','history');
					echo '{"message":{"text":" ⎛° ͜ʖ°⎞⎠ \n【# 이어보기】\n======================\n▶ 최근시청\n▶ 시청이력\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "},"keyboard":{"type": "buttons","buttons":["최근시청","시청이력","Home","뒤로"]}}';						
				}else{
				switch($tmp_categ){
					case'국내영화':
						updateStatus('history_watching','komv_watching_view');
						$tmp = explode('】',$tmp_titles);
						$tmp_mv_title_ko = $tmp[0];
						$tmp_mv_title_ko = str_replace('【','',$tmp_mv_title_ko);
						$tmp_mv_title_en = $tmp[1];

						// echo '{"message":{"text":"오홍'.$mv_title_ko.','.$mv_title_en.','.$release_date.'"}}';
						$komv_result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_title_ko = \"".$tmp_mv_title_ko."\" AND mv_title_en = \"".$tmp_mv_title_en."\"");

						$komv_row = mysqli_fetch_array($komv_result);
						$mv_id = $komv_row['mv_id'];
						$mv_title_ko = $komv_row['mv_title_ko'];
						$mv_title_en = $komv_row['mv_title_en'];
						$score = $komv_row['score'];
						$genre = $komv_row['genre'];
						$country = $komv_row['country'];
						$release_date = $komv_row['release_date'];
						$actor = $komv_row['actor'];
						$grade = $komv_row['grade'];
						$audience = $komv_row['audience'];
						$summary = $komv_row['summary'];
						$preview = $komv_row['preview'];
						$mv_src = $komv_row['mv_src'];

						// 2018-08-22 추가내용 : 감독 컬럼 추가, 포스터 컬럼 추가
						$director = $komv_row['director'];
						$poster = $komv_row['poster'];

						// 2018-08-21 추가내용 : 영화 클릭했을 때도 영화 리스트 버튼 띄워주기
						// $prevstatus로 최신, 가나다순 구분함
						$resultList = array();
						$result;
						if($prevstatus=='komv_watching1'){
							$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE  mv_id!=".$mv_id." AND country='한국' ORDER BY release_date DESC");
						}else{
							$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE  mv_id!=".$mv_id." AND country='한국' ORDER BY mv_title_ko");
						}

						// 현재 선택한 영화 맨 위로
						array_push($resultList,'"▶ 【'.$mv_title_ko.'】'.$mv_title_en.'"');
						// 나머지 영화 주르륵
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
						}
						// Home과 뒤로 
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmpList = implode(", ", $resultList);

						// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
						mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),NULL,".$user_id.",".$mv_id.",\"".$tmp_titles."\")");

						echo'{
								"message":
									{
							  			"text": "======================\n【'.$mv_title_ko.'】\n'.$mv_title_en.'\n======================\n▶개요\n'.$genre.' | '.$country.' | 평점: '.$score.'\n'.$release_date.'개봉 | '.$grade.'\n▶ 감독 \n'.$director.' \n▶ 출연진 \n'.$actor.' \n▶ 줄거리 \n'.$summary.' \n▶ 네이버영화 link\n'.$preview.'",
									    "photo": {
									      "url": "'.$poster.'",
									      "width": 203,
									      "height": 290
									    },
							  			"message_button": {
							    			"label": "영화보기",
							    			"url": "'.$mv_src.'"
						  				}
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmpList.']
									}
							}';						
						break;
					case'해외영화':
						updateStatus('history_watching','fgmv_watching_view');
						
						$tmp = explode('】',$tmp_titles);
						$tmp_mv_title_ko = $tmp[0];
						$tmp_mv_title_ko = str_replace('【','',$tmp_mv_title_ko);
						$tmp_mv_title_en = $tmp[1];

						// echo '{"message":{"text":"오홍'.$mv_title_ko.','.$mv_title_en.','.$release_date.'"}}';
						$komv_result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_title_ko = \"".$tmp_mv_title_ko."\" AND mv_title_en = \"".$tmp_mv_title_en."\"");

						$komv_row = mysqli_fetch_array($komv_result);
						$mv_id = $komv_row['mv_id'];
						$mv_title_ko = $komv_row['mv_title_ko'];
						$mv_title_en = $komv_row['mv_title_en'];
						$score = $komv_row['score'];
						$genre = $komv_row['genre'];
						$country = $komv_row['country'];
						$release_date = $komv_row['release_date'];
						$actor = $komv_row['actor'];
						$grade = $komv_row['grade'];
						$audience = $komv_row['audience'];
						$summary = $komv_row['summary'];
						$preview = $komv_row['preview'];
						$mv_src = $komv_row['mv_src'];

						// 2018-08-22 추가내용 : 감독 컬럼 추가, 포스터 컬럼 추가
						$director = $komv_row['director'];
						$poster = $komv_row['poster'];

						// 2018-08-21 추가내용 : 영화 클릭했을 때도 영화 리스트 버튼 띄워주기
						// $prevstatus로 최신, 가나다순 구분함
						$resultList = array();
						$result;

						if($prevstatus=='fgmv_watching1'){
							$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_id!=".$mv_id." AND country!='한국' ORDER BY release_date DESC");
						}else{
							$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_id!=".$mv_id." AND country!='한국' ORDER BY mv_title_en");
						}
						
						// 현재 선택한 영화 맨 위로
						array_push($resultList,'"▶ 【'.$mv_title_ko.'】'.$mv_title_en.'"');
						// 나머지 영화 주르륵
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
						}
						// Home과 뒤로 
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmpList = implode(", ", $resultList);

						// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
						mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),NULL,".$user_id.",".$mv_id.",\"".$tmp_titles."\")");


						echo'{
								"message":
									{
							  			"text": "======================\n【'.$mv_title_ko.'】\n'.$mv_title_en.'\n======================\n▶개요\n'.$genre.' | '.$country.' | 평점: '.$score.'\n'.$release_date.'개봉 | '.$grade.'\n▶ 감독 \n'.$director.' \n▶ 출연진 \n'.$actor.' \n▶ 줄거리 \n'.$summary.' \n▶ 네이버영화 link\n'.$preview.'",
									    "photo": {
									      "url": "'.$poster.'",
									      "width": 203,
									      "height": 290
									    },
							  			"message_button": {
							    			"label": "영화보기",
							    			"url": "'.$mv_src.'"
						  				}
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmpList.']
									}
							}';							
					
						break;
					case'국내드라마':
						updateStatus('history_watching','kodr_watching_view_src');
						$tmp = explode('.',$tmp_titles);

						$tmp_braodcast = $tmp[0];		// [KBS2]
						$tmp_broadcast = preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}\【\】]/i", "", $tmp_braodcast); // KBS2
						$tmp_tv_title = $tmp[1];		// 연애의 발견
						$tmp_tv_idx = $tmp[2];		// 1화
						$tmp_tv_idx = preg_replace("/[^0-9]*/s", "", $tmp_tv_idx); // 1
						$tmp_braod_date = $tmp[3];		// 2014-08-18

						//방송사와 제목으로 tv_id를 찾음
						$tv_result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE broadcast = \"".$tmp_broadcast."\" AND tv_title = \"".$tmp_tv_title."\"");
						$tv_row = mysqli_fetch_array($tv_result);
						$tv_id = $tv_row['tv_id'];

						//회차와 방송일자, tv_id로 tv_src테이블 조회
						$tv_info_result = mysqli_query($conn,"SELECT * FROM tbl_tv_src WHERE tv_idx = \"".$tmp_tv_idx."\" AND broad_date = \"".$tmp_braod_date."\" AND tv_id = \"".$tv_id."\"");
						$tv_info_row = mysqli_fetch_array($tv_info_result);
						$tv_src_id = $tv_info_row['tv_src_id'];
						$tv_src = $tv_info_row['tv_src'];
						$summary = $tv_info_row['summary'];

						//tv_id로 각 회차 정보를 찾음(방송일자로 정렬)
						$tv_src_result = mysqli_query($conn, "SELECT * FROM tbl_tv_src WHERE tv_src_id != ".$tv_src_id." AND tv_id = ".$tv_id." ORDER BY broad_date");
						$tv_src_resultList = array();

						// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
						mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),".$tv_src_id.",".$user_id.",NULL,\"".$tmp_titles."\")");


						array_push($tv_src_resultList,'"▶ 【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tmp_tv_idx.'화.'.$tmp_braod_date.'"');
						while($tv_src_row = mysqli_fetch_array($tv_src_result)){
							array_push($tv_src_resultList,'"【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tv_src_row['tv_idx'].'화.'.$tv_src_row['broad_date'].'"');
						}
						array_push($tv_src_resultList,'"Home"');
						array_push($tv_src_resultList,'"뒤로"');
						// array_push($tv_src_resultList,'"책갈피"');
						$tmp1 = implode(", ", $tv_src_resultList);
			            echo'{
			                  "message":
				                     {
				                          "text": "⎛° ͜ʖ°⎞⎠\n======================\n【'.$tmp_broadcast.'】.'.$tmp_tv_title.'\n======================\n▶ '.$tmp[2].' \n▶ 방송일자 : '.$tmp_braod_date.'\n▶ 줄거리 \n'.$summary.'",
						    			"message_button": {
							      			"label": "바로 시청하기",
							      			"url": "'.$tv_src.'"
							    			}
					    			},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp1.']
									}
							}';	
						break;
					case'국내예능':
						updateStatus('history_watching','koen_watching_view_src');
						$tmp = explode('.',$tmp_titles);

						$tmp_braodcast = $tmp[0];		// [KBS2]
						$tmp_broadcast = preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}\【\】]/i", "", $tmp_braodcast); // KBS2
						$tmp_tv_title = $tmp[1];		// 연애의 발견
						$tmp_tv_idx = $tmp[2];		// 1화
						$tmp_tv_idx = preg_replace("/[^0-9]*/s", "", $tmp_tv_idx); // 1
						$tmp_braod_date = $tmp[3];		// 2014-08-18

						//방송사와 제목으로 tv_id를 찾음
						$tv_result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE broadcast = \"".$tmp_broadcast."\" AND tv_title = \"".$tmp_tv_title."\"");
						$tv_row = mysqli_fetch_array($tv_result);
						$tv_id = $tv_row['tv_id'];

						//회차와 방송일자, tv_id로 tv_src테이블 조회
						$tv_info_result = mysqli_query($conn,"SELECT * FROM tbl_tv_src WHERE tv_idx = \"".$tmp_tv_idx."\" AND broad_date = \"".$tmp_braod_date."\" AND tv_id = \"".$tv_id."\"");
						$tv_info_row = mysqli_fetch_array($tv_info_result);
						$tv_src_id = $tv_info_row['tv_src_id'];
						$tv_src = $tv_info_row['tv_src'];
						$summary = $tv_info_row['summary'];

						//tv_id로 각 회차 정보를 찾음(방송일자로 정렬)
						$tv_src_result = mysqli_query($conn, "SELECT * FROM tbl_tv_src WHERE tv_src_id != ".$tv_src_id." AND tv_id = ".$tv_id." ORDER BY broad_date");
						$tv_src_resultList = array();

						// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
						mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),".$tv_src_id.",".$user_id.",NULL,\"".$tmp_titles."\")");

						array_push($tv_src_resultList,'"▶ 【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tmp_tv_idx.'화.'.$tmp_braod_date.'"');
						while($tv_src_row = mysqli_fetch_array($tv_src_result)){
							array_push($tv_src_resultList,'"【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tv_src_row['tv_idx'].'화.'.$tv_src_row['broad_date'].'"');
						}
						array_push($tv_src_resultList,'"Home"');
						array_push($tv_src_resultList,'"뒤로"');
						// array_push($tv_src_resultList,'"책갈피"');
						$tmp1 = implode(", ", $tv_src_resultList);
			            echo'{
			                  "message":
				                     {
				                          "text": "⎛° ͜ʖ°⎞⎠\n======================\n【'.$tmp_broadcast.'】.'.$tmp_tv_title.'\n======================\n▶ '.$tmp[2].' \n▶ 방송일자 : '.$tmp_braod_date.'\n▶ 줄거리 \n'.$summary.'",
						    			"message_button": {
							      			"label": "바로 시청하기",
							      			"url": "'.$tv_src.'"
							    			}
					    			},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp1.']
									}
							}';				
						break;
					default:
						echo'{
								"message":{
									"text":"올바르지 않은 값입니다."
								},
								"keyboard":{
									"type":"buttons",
									"buttons":["Home","뒤로"]
								}
						}';					
						break;																		
				}
			}

				break;

			//========================================================================================================================================
			// Level-2 국내영화 MAIN 화면
			//========================================================================================================================================
			case 'komv_watching':
				switch ($content) {
					case '전체목록(최신순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE country='한국' ORDER BY release_date DESC");
						while($row = mysqli_fetch_array($result)){
							// 2018-08-23 추가/수정 내용 : 버튼에서 개봉 일자 지워버림
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);
						
						updateStatus('komv_watching1','komv_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내영화】\n ┗전체목록(최신순)\n======================\n▶버튼을 선택하면 \n상세정보가 보입니다.\n바로보기도 가능!  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	
						break;
					case '전체목록(가나다순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE country='한국' ORDER BY mv_title_ko");
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);
						
						updateStatus('komv_watching2','komv_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내영화】\n ┗전체목록(가나다순)\n======================\n▶버튼을 선택하면 \n상세정보가 보입니다.\n바로보기도 가능!  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	
						break;
					case '이름으로검색':
						updateStatus('komv_watching3','komv_watching_search');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내영화】\n ┗이름으로검색\n======================\n▶보고싶은 영화 제목 검색 "
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						break;

					case '뒤로':
						updateStatus('komv_watching','watching');
						echo '{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 컨텐츠 시청하기】\n======================\n▶ 국내영화\n▶ 해외영화\n▶ 국내드라마\n▶ 국내예능\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["국내영화","해외영화","장르별영화","국내드라마","국내예능","Home","뒤로"]
									}
								}';	
						break;

					default:
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
									}
							}';
						break;
				}			
				break;	

			//========================================================================================================================================
			// Level-3 국내영화 이름 선택했을 때, 정보 보여주고 링크주기
			//========================================================================================================================================				
			case 'komv_watching_view':
				// array_push($resultList,'"'.$row['mv_title_ko'].'.'.$row['mv_title_en'].'.'.$row['release_date'].'"');


				if($content=='뒤로'){
					updateStatus('komv_watching_view','komv_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}else{
					$tmp = explode('】',$content);
					$tmp_mv_title_ko = $tmp[0];
					$tmp_mv_title_ko = str_replace('【','',$tmp_mv_title_ko);
					$tmp_mv_title_en = $tmp[1];

					// echo '{"message":{"text":"오홍'.$mv_title_ko.','.$mv_title_en.','.$release_date.'"}}';
					$komv_result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_title_ko = \"".$tmp_mv_title_ko."\" AND mv_title_en = \"".$tmp_mv_title_en."\"");

					$komv_row = mysqli_fetch_array($komv_result);
					$mv_id = $komv_row['mv_id'];
					$mv_title_ko = $komv_row['mv_title_ko'];
					$mv_title_en = $komv_row['mv_title_en'];
					$score = $komv_row['score'];
					$genre = $komv_row['genre'];
					$country = $komv_row['country'];
					$release_date = $komv_row['release_date'];
					$actor = $komv_row['actor'];
					$grade = $komv_row['grade'];
					$audience = $komv_row['audience'];
					$summary = $komv_row['summary'];
					$preview = $komv_row['preview'];
					$mv_src = $komv_row['mv_src'];

					// 2018-08-22 추가내용 : 감독 컬럼 추가, 포스터 컬럼 추가
					$director = $komv_row['director'];
					$poster = $komv_row['poster'];

					// 2018-08-21 추가내용 : 영화 클릭했을 때도 영화 리스트 버튼 띄워주기
					// $prevstatus로 최신, 가나다순 구분함
					$resultList = array();
					$result;
					if($prevstatus=='komv_watching1'){
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE  mv_id!=".$mv_id." AND country='한국' ORDER BY release_date DESC");
					}else{
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE  mv_id!=".$mv_id." AND country='한국' ORDER BY mv_title_ko");
					}

					// 현재 선택한 영화 맨 위로
					array_push($resultList,'"▶ 【'.$mv_title_ko.'】'.$mv_title_en.'"');
					// 나머지 영화 주르륵
					while($row = mysqli_fetch_array($result)){
						array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
					}
					// Home과 뒤로 
					array_push($resultList,'"Home"');
					array_push($resultList,'"뒤로"');
					$tmpList = implode(", ", $resultList);

					// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
					mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),NULL,".$user_id.",".$mv_id.",\"".$content."\")");


					echo'{
							"message":
								{
						  			"text": "======================\n【'.$mv_title_ko.'】\n'.$mv_title_en.'\n======================\n▶개요\n'.$genre.' | '.$country.' | 평점: '.$score.'\n'.$release_date.'개봉 | '.$grade.'\n▶ 감독 \n'.$director.' \n▶ 출연진 \n'.$actor.' \n▶ 줄거리 \n'.$summary.' \n▶ 네이버영화 link\n'.$preview.'",
								    "photo": {
								      "url": "'.$poster.'",
								      "width": 203,
								      "height": 290
								    },
						  			"message_button": {
						    			"label": "영화보기",
						    			"url": "'.$mv_src.'"
					  				}
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":['.$tmpList.']
								}
						}';	
					break;
				}
			//========================================================================================================================================
			// Level-3 국내영화 이름으로 검색
			//========================================================================================================================================
			case 'komv_watching_search':
				if($content=='뒤로'){
					updateStatus('komv_watching_search','komv_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}
				$resultList = array();

				if($result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE country=\"한국\" AND mv_title_ko LIKE \"%".$content."%\"ORDER BY release_date DESC")){
					$row_cnt = mysqli_num_rows($result);
					if($row_cnt==0){
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내영화】\n ┗이름으로검색  『실패』\n======================\n  (ಢ‸ಢ) (ಢ‸ಢ)\n▶검색 결과가 없습니다. \n다시 검색해주세요\n▶ Home 화면으로 이동\n\"Home\" 입력 후 전송\n▶ 뒤로가기 \n\"뒤로\" 입력 후 전송"
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						// echo'{"message":{"text":"검색 결과가 없습니다."},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
					}else{
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
							}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);
						
						updateStatus('komv_watching','komv_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내영화】\n ┗이름으로검색  『성공』\n======================\n▶검색어 : '.$content.' \n▶검색 결과를 확인하세요. \n▶버튼을 선택하면 \n  상세정보가 보입니다.\n  바로보기도 가능!  ༼ つ ◕_◕ ༽つ"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';							
					}

				}else{
					echo'{"message":{"text":"올바르지 않은 값이에요"},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
				}


				break;
			//========================================================================================================================================
			// Level-2 해외영화 MAIN 화면
			//========================================================================================================================================
			case 'fgmv_watching':
				switch ($content) {
					case '전체목록(최신순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE country!='한국' ORDER BY release_date DESC");
						while($row = mysqli_fetch_array($result)){
							// 2018-08-23 추가/수정 내용 : 버튼에서 개봉 일자 지워버림
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);
						
						updateStatus('fgmv_watching1','fgmv_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 해외영화】\n ┗전체목록(최신순)\n======================\n▶버튼을 선택하면 \n상세정보가 보입니다.\n바로보기도 가능!  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	
						break;
					case '전체목록(ABC순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE country!='한국' ORDER BY mv_title_en");
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);
						
						updateStatus('fgmv_watching2','fgmv_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 해외영화】\n ┗전체목록(ABC순)\n======================\n▶버튼을 선택하면 \n상세정보가 보입니다.\n바로보기도 가능!  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	
						break;
					case '이름으로검색':
						updateStatus('fgmv_watching3','fgmv_watching_search');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내영화】\n ┗이름으로검색\n======================\n▶보고싶은 영화 제목 검색 "
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						break;

					case '뒤로':
						updateStatus('fgmv_watching','watching');
						echo '{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 컨텐츠 시청하기】\n======================\n▶ 국내영화\n▶ 해외영화\n▶ 국내드라마\n▶ 국내예능\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["국내영화","해외영화","장르별영화","국내드라마","국내예능","Home","뒤로"]
									}
								}';	
						break;

					default:
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 해외영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(ABC순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 ABC순 정렬\n  ☞ 전체목록(ABC)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["전체목록(최신순)","전체목록(ABC순)","이름으로검색","Home","뒤로"]
									}
							}';
						break;
				}			
				break;	
			//========================================================================================================================================
			// Level-3 해외영화 이름 선택했을 때, 정보 보여주고 링크주기
			//========================================================================================================================================				
			case 'fgmv_watching_view':
				// array_push($resultList,'"'.$row['mv_title_ko'].'.'.$row['mv_title_en'].'.'.$row['release_date'].'"');

				if($content=='뒤로'){
					updateStatus('fgmv_watching_view','fgmv_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 해외영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(ABC순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 ABC순 정렬\n  ☞ 전체목록(ABC)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(ABC순)","이름으로검색","Home","뒤로"]
								}
						}';
				}else{
					$tmp = explode('】',$content);
					$tmp_mv_title_ko = $tmp[0];
					$tmp_mv_title_ko = str_replace('【','',$tmp_mv_title_ko);
					$tmp_mv_title_en = $tmp[1];

					// echo '{"message":{"text":"오홍'.$mv_title_ko.','.$mv_title_en.','.$release_date.'"}}';
					$komv_result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_title_ko = \"".$tmp_mv_title_ko."\" AND mv_title_en = \"".$tmp_mv_title_en."\"");

					$komv_row = mysqli_fetch_array($komv_result);
					$mv_id = $komv_row['mv_id'];
					$mv_title_ko = $komv_row['mv_title_ko'];
					$mv_title_en = $komv_row['mv_title_en'];
					$score = $komv_row['score'];
					$genre = $komv_row['genre'];
					$country = $komv_row['country'];
					$release_date = $komv_row['release_date'];
					$actor = $komv_row['actor'];
					$grade = $komv_row['grade'];
					$audience = $komv_row['audience'];
					$summary = $komv_row['summary'];
					$preview = $komv_row['preview'];
					$mv_src = $komv_row['mv_src'];

					// 2018-08-22 추가내용 : 감독 컬럼 추가, 포스터 컬럼 추가
					$director = $komv_row['director'];
					$poster = $komv_row['poster'];

					// 2018-08-21 추가내용 : 영화 클릭했을 때도 영화 리스트 버튼 띄워주기
					// 한데 $prevstatus로 최신, 가나다순 구분함
					$resultList = array();
					$result;

					if($prevstatus=='fgmv_watching1'){
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_id!=".$mv_id." AND country!='한국' ORDER BY release_date DESC");
					}else{
						$result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE mv_id!=".$mv_id." AND country!='한국' ORDER BY mv_title_en");
					}
					
					// 현재 선택한 영화 맨 위로
					array_push($resultList,'"▶ 【'.$mv_title_ko.'】'.$mv_title_en.'"');
					// 나머지 영화 주르륵
					while($row = mysqli_fetch_array($result)){
						array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
					}
					// Home과 뒤로 
					array_push($resultList,'"Home"');
					array_push($resultList,'"뒤로"');
					$tmpList = implode(", ", $resultList);

					// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
					mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),NULL,".$user_id.",".$mv_id.",\"".$content."\")");


					echo'{
							"message":
								{
						  			"text": "======================\n【'.$mv_title_ko.'】\n'.$mv_title_en.'\n======================\n▶개요\n'.$genre.' | '.$country.' | 평점: '.$score.'\n'.$release_date.'개봉 | '.$grade.'\n▶ 감독 \n'.$director.' \n▶ 출연진 \n'.$actor.' \n▶ 줄거리 \n'.$summary.' \n▶ 네이버영화 link\n'.$preview.'",
								    "photo": {
								      "url": "'.$poster.'",
								      "width": 203,
								      "height": 290
								    },
						  			"message_button": {
						    			"label": "영화보기",
						    			"url": "'.$mv_src.'"
					  				}
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":['.$tmpList.']
								}
						}';	
					break;
				}
			//========================================================================================================================================
			// Level-3 해외영화 이름으로 검색
			//========================================================================================================================================
			case 'fgmv_watching_search':

				if($content=='뒤로'){
					updateStatus('fgmv_watching_search','fgmv_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 해외영화】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(ABC순)\n▶ 이름으로검색\n▶ Home\n======================\n개봉일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 ABC순 정렬\n  ☞ 전체목록(ABC)\n영화이름(한글)으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(ABC순)","이름으로검색","Home","뒤로"]
								}
						}';
				}

				$resultList = array();

				if($result = mysqli_query($conn,"SELECT * FROM tbl_movie_base WHERE country!='한국' AND mv_title_ko LIKE \"%".$content."%\" OR mv_title_en LIKE \"%".$content."%\" ORDER BY release_date DESC")){
					$row_cnt = mysqli_num_rows($result);
					if($row_cnt==0){
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 해외영화】\n ┗이름으로검색  『실패』\n======================\n  (ಢ‸ಢ) (ಢ‸ಢ)\n▶검색 결과가 없습니다. \n다시 검색하거나\n▶ Home 화면으로 이동\n\"Home\" 입력 후 전송\n▶ 뒤로가기 \n\"뒤로\" 입력 후 전송"
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						// echo'{"message":{"text":"검색 결과가 없습니다."},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
					}else{
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
							}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);
						
						updateStatus('fgmv_watching','fgmv_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 해외영화】\n ┗이름으로검색  『성공』\n======================\n▶검색어 : '.$content.' \n▶검색 결과를 확인하세요. \n▶버튼을 선택하면 \n  상세정보가 보입니다.\n  바로보기도 가능!  ༼ つ ◕_◕ ༽つ"
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';							
					}

				}else{
					echo'{"message":{"text":"올바르지 않은 값이에요"},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
				}


				break;

			//========================================================================================================================================
			// Level-2 장르별영화 MAIN 화면
			//========================================================================================================================================	
			case 'genremv_watching':
				$genremv_result = mysqli_query($conn, "SELECT * FROM tbl_movie_base WHERE genre LIKE \"%".$content."%\"");
				$resultList = array();
				$search_cnt = mysqli_num_rows($genremv_result);
				if($search_cnt ==0){
					echo '{
							"message":{
								"text":"조회된 영화가 없습니다. \n장르를 다시 선택해 주세요"
							},
							"keyboard":{
								"type":"buttons",
								"buttons":["드라마","판타지","서부","공포","멜로/로맨스","모험","스릴러","느와르","컬트","다큐멘터리","코미디","가족","미스터리","전쟁","애니메이션","범죄","뮤지컬","SF","액션","무협","에로","서스펜스","서사","블랙코미디","실험","공연실황","Home","뒤로"]
							}
					}';
				}elseif($search_cnt==1){
					$row = mysqli_fetch_array($genremv_result);
					
					array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
					array_push($resultList,'"Home"');
					array_push($resultList,'"뒤로"');
					$tmp = implode(", ", $resultList);
					echo '{
							"message":{
								"text":"'.$content.' 장르에는 \n'.$search_cnt.'건의 검색 결과가 있습니다."
							},
							"keyboard":{
								"type":"buttons",
								"buttons":['.$tmp.']
							}
					}';								
				}else{

					while($row = mysqli_fetch_array($genremv_result)){
						array_push($resultList,'"【'.$row['mv_title_ko'].'】'.$row['mv_title_en'].'"');
						}
					array_push($resultList,'"Home"');
					array_push($resultList,'"뒤로"');
					$tmp = implode(", ", $resultList);
						
					echo '{
							"message":{
								"text":"'.$content.' 장르에는 \n'.$search_cnt.'건의 검색 결과가 있습니다."
							},
							"keyboard":{
								"type":"buttons",
								"buttons":['.$tmp.']
							}
					}';							
				}
				break;
			//========================================================================================================================================
			// Level-2 장르별영화 영화 이름 클릭 했을 때 화면
			//========================================================================================================================================	
			case 'genremv_watching':
				echo'{
						"message":{
							"text":"아직 준비중인 기능입니다. \n빠른 시일 내에 완성하겠습니다."
						},
						"keyboard":{
							"type":"buttons",
							"buttons":["Home"]
						}
				}';
				
				break;				
			//========================================================================================================================================
			// Level-2 국내드라마 MAIN 화면
			//========================================================================================================================================
			case 'kodr_watching':
				switch ($content) {
					case '전체목록(최신순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE genre = '드라마' ORDER BY term DESC");
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['broadcast'].'】 '.$row['tv_title'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);

						updateStatus('kodr_watching','kodr_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내드라마】\n ┗전체목록(최신순)\n======================\n▶드라마 제목을 선택하면 \n  상세정보가 보입니다.\n  각 회차로 이동 가능!  \n  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	

						// echo'{"message":{"text":"전체목록(최신순)"},"keyboard":{"type": "buttons","buttons":['.$tmp.']}}';	
						break;
					case '전체목록(가나다순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE genre = '드라마' ORDER BY tv_title");
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['broadcast'].'】 '.$row['tv_title'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);

						updateStatus('kodr_watching','kodr_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내드라마】\n ┗전체목록(가나다순)\n======================\n▶드라마 제목을 선택하면 \n  상세정보가 보입니다.\n  각 회차로 이동 가능!  \n  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	

						break;
					case '이름으로검색':
						updateStatus('kodr_watching','kodr_watching_search');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내드라마】\n ┗이름으로검색\n======================\n▶보고싶은 드라마 제목 검색 "
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						break;

					case '뒤로':
						updateStatus('kodr_watching','watching');
						echo '{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 컨텐츠 시청하기】\n======================\n▶ 국내영화\n▶ 해외영화\n▶ 국내드라마\n▶ 국내예능\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["국내영화","해외영화","장르별영화","국내드라마","국내예능","Home","뒤로"]
									}
								}';	
						break;

					default:
						echo'{"message":{"text":"올바르지 않은 값이에요\npoint: 국내드라마"},"keyboard":{"type": "buttons","buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]}}';	
						break;
				}			
				break;

			//========================================================================================================================================
			// Level-2 국내드라마 검색 화면
			//========================================================================================================================================			
			case 'kodr_watching_search':
				if($content=='뒤로'){
					updateStatus('kodr_watching_search','kodr_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내드라마】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n드라마 제목으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}

				$resultList = array();

				if($result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE genre='드라마' AND tv_title LIKE \"%".$content."%\" ORDER BY tv_title")){
					$row_cnt = mysqli_num_rows($result);
					if($row_cnt==0){
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내드라마】\n ┗이름으로검색  『실패』\n======================\n  (ಢ‸ಢ) (ಢ‸ಢ)\n▶검색 결과가 없습니다. \n다시 검색하거나\n▶ Home 화면으로 이동\n\"Home\" 입력 후 전송\n▶ 뒤로가기 \n\"뒤로\" 입력 후 전송"
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						// echo'{"message":{"text":"검색 결과가 없습니다."},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
					}else{
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['broadcast'].'】 '.$row['tv_title'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);

						updateStatus('kodr_watching','kodr_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내드라마】\n ┗이름으로검색\n======================\n▶드라마 제목을 선택하면 \n  상세정보가 보입니다.\n  각 회차로 이동 가능!  \n  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	

						break;
					}

				}else{
					echo'{"message":{"text":"올바르지 않은 값이에요"},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
				}


				break;
			

			//========================================================================================================================================
			//========================================================================================================================================

			//드라마 이름 클릭했을 때, base테이블에 있는 정보와, 회차를 버튼으로 리턴함
			case 'kodr_watching_view':
				if($content=='뒤로'){
					updateStatus('kodr_watching_view','kodr_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내드라마】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n드라마 제목으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}else{
					$tmp = explode('】',$content);
					$tmp_broadcast = $tmp[0];
					$tmp_broadcast = str_replace('【','',$tmp_broadcast);
					$tmp_tv_title = $tmp[1];
					$tmp_tv_title = ltrim($tmp_tv_title);

					$tv_result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE broadcast = \"".$tmp_broadcast."\" AND tv_title = \"".$tmp_tv_title."\"");
					$tv_row = mysqli_fetch_array($tv_result);

					$tv_id = $tv_row['tv_id'];
					$tv_title = $tv_row['tv_title'];
					$genre = $tv_row['genre'];
					$grade = $tv_row['grade'];
					$broadcast = $tv_row['broadcast'];
					$term = $tv_row['term'];
					$highest_rating = $tv_row['highest_rating'];
					$summary = $tv_row['summary'];
					$preview = $tv_row['preview'];
					$actor = $tv_row['actor'];
					$homepage = $tv_row['homepage'];

					$tv_src_result = mysqli_query($conn, "SELECT * FROM tbl_tv_src WHERE tv_id = \"".$tv_id."\" ORDER BY broad_date");
					$tv_src_resultList = array();

					$term1 = mb_strcut($term, 0, 24);
					while($tv_src_row = mysqli_fetch_array($tv_src_result)){
						array_push($tv_src_resultList,'"【'.$broadcast.'】.'.$tv_title.'.'.$tv_src_row['tv_idx'].'화.'.$tv_src_row['broad_date'].'"');
					}

					array_push($tv_src_resultList,'"Home"');
					$tmp = implode(", ", $tv_src_resultList);

					updateStatus('kodr_watching_view','kodr_watching_view_src');
		            echo'{
		                  "message":
		                     {
		                          "text": "⎛° ͜ʖ°⎞⎠\n======================\n【'.$broadcast.'】.'.$tv_title.'\n======================\n▶개요\n'.$genre.' | '.$grade.'\n시청률 : '.$highest_rating.'\n'.$term1.' \n▶ 출연진 \n'.$actor.' \n▶ 줄거리 \n'.$summary.' \n▶ 홈페이지 link\n'.$homepage.'"
		                     },
		                  "keyboard":
		                     {
		                        "type": "buttons",
		                        "buttons":['.$tmp.']
		                     }
		               }';   				
				}
				break;

			//========================================================================================================================================
			//========================================================================================================================================
			//드라마 이름 선택하고 나서 회차 선택했을 때, src테이블에 있는 정보와, 스트리밍 링크 리턴함
			case 'kodr_watching_view_src':
				if($content=='뒤로'){
					updateStatus('kodr_watching_view_src','kodr_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내드라마】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n드라마 제목으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}else{
					// [KBS2].연애의 발견.1화.2014-08-18
					$tmp = explode('.',$content);

					$tmp_braodcast = $tmp[0];		// [KBS2]
					$tmp_broadcast = preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}\【\】]/i", "", $tmp_braodcast); // KBS2
					$tmp_tv_title = $tmp[1];		// 연애의 발견
					$tmp_tv_idx = $tmp[2];		// 1화
					$tmp_tv_idx = preg_replace("/[^0-9]*/s", "", $tmp_tv_idx); // 1
					$tmp_braod_date = $tmp[3];		// 2014-08-18

					//방송사와 제목으로 tv_id를 찾음
					$tv_result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE broadcast = \"".$tmp_broadcast."\" AND tv_title = \"".$tmp_tv_title."\"");
					$tv_row = mysqli_fetch_array($tv_result);
					$tv_id = $tv_row['tv_id'];

					//회차와 방송일자, tv_id로 tv_src테이블 조회
					$tv_info_result = mysqli_query($conn,"SELECT * FROM tbl_tv_src WHERE tv_idx = \"".$tmp_tv_idx."\" AND broad_date = \"".$tmp_braod_date."\" AND tv_id = \"".$tv_id."\"");
					$tv_info_row = mysqli_fetch_array($tv_info_result);
					$tv_src_id = $tv_info_row['tv_src_id'];
					$tv_src = $tv_info_row['tv_src'];
					$summary = $tv_info_row['summary'];

					//tv_id로 각 회차 정보를 찾음(방송일자로 정렬)
					$tv_src_result = mysqli_query($conn, "SELECT * FROM tbl_tv_src WHERE tv_src_id != ".$tv_src_id." AND tv_id = ".$tv_id." ORDER BY broad_date");
					$tv_src_resultList = array();

					// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
					mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),".$tv_src_id.",".$user_id.",NULL,\"".$content."\")");


					array_push($tv_src_resultList,'"▶ 【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tmp_tv_idx.'화.'.$tmp_braod_date.'"');
					while($tv_src_row = mysqli_fetch_array($tv_src_result)){
						array_push($tv_src_resultList,'"【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tv_src_row['tv_idx'].'화.'.$tv_src_row['broad_date'].'"');
					}
					array_push($tv_src_resultList,'"Home"');
					array_push($tv_src_resultList,'"뒤로"');
					// array_push($tv_src_resultList,'"책갈피"');
					$tmp1 = implode(", ", $tv_src_resultList);
		            echo'{
		                  "message":
			                     {
			                          "text": "⎛° ͜ʖ°⎞⎠\n======================\n【'.$tmp_broadcast.'】.'.$tmp_tv_title.'\n======================\n▶ '.$tmp[2].' \n▶ 방송일자 : '.$tmp_braod_date.'\n▶ 줄거리 \n'.$summary.'",
					    			"message_button": {
						      			"label": "바로 시청하기",
						      			"url": "'.$tv_src.'"
						    			}
				    			},
							"keyboard":
								{
									"type": "buttons",
									"buttons":['.$tmp1.']
								}
						}';
				}
				break;

			//========================================================================================================================================
			// Level-2 국내예능 MAIN 화면
			//========================================================================================================================================
			case 'koen_watching':
				switch ($content) {
					case '전체목록(최신순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE genre != '드라마' ORDER BY term DESC");
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['broadcast'].'】 '.$row['tv_title'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);

						updateStatus('koen_watching','koen_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내예능】\n ┗전체목록(최신순)\n======================\n▶예능 프로 제목을 선택하면 \n  상세정보가 보입니다.\n  각 회차로 이동 가능!  \n  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	

						// echo'{"message":{"text":"전체목록(최신순)"},"keyboard":{"type": "buttons","buttons":['.$tmp.']}}';	
						break;
					case '전체목록(가나다순)':
						$resultList = array();
						$result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE genre != '드라마' ORDER BY tv_title");
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['broadcast'].'】 '.$row['tv_title'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);

						updateStatus('koen_watching','koen_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내예능】\n ┗전체목록(가나다순)\n======================\n▶예능 프로 제목을 선택하면 \n  상세정보가 보입니다.\n  각 회차로 이동 가능!  \n  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	

						break;
					case '이름으로검색':
						updateStatus('koen_watching','koen_watching_search');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내예능】\n ┗이름으로검색\n======================\n▶보고싶은 예능 프로 제목 검색 "
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						break;

					case '뒤로':
						updateStatus('koen_watching','watching');
						echo '{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠ \n【# 컨텐츠 시청하기】\n======================\n▶ 국내영화\n▶ 해외영화\n▶ 국내드라마\n▶ 국내예능\n======================\n팝콘 챙기시고  ٩(͡◕_͡◕ \n재밌게 즐기세요. "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":["국내영화","해외영화","장르별영화","국내드라마","국내예능","Home","뒤로"]
									}
								}';	
						break;

					default:
						echo'{"message":{"text":"올바르지 않은 값이에요\npoint:국내예능"},"keyboard":{"type": "buttons","buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]}}';	
						break;
				}			
				break;

			//========================================================================================================================================
			// Level-2 국내예능 검색 화면
			//========================================================================================================================================			
			case 'koen_watching_search':
				if($content=='뒤로'){
					updateStatus('koen_watching_search','koen_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내예능】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n예능 프로 제목으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}

				$resultList = array();

				if($result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE genre!='드라마' AND tv_title LIKE \"%".$content."%\" ORDER BY tv_title")){
					$row_cnt = mysqli_num_rows($result);
					if($row_cnt==0){
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내예능】\n ┗이름으로검색  『실패』\n======================\n  (ಢ‸ಢ) (ಢ‸ಢ)\n▶검색 결과가 없습니다. \n다시 검색하거나\n▶ Home 화면으로 이동\n\"Home\" 입력 후 전송\n▶ 뒤로가기 \n\"뒤로\" 입력 후 전송"
									},
								"keyboard":
									{
										"type": "text"
									}
							}';	
						// echo'{"message":{"text":"검색 결과가 없습니다."},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
					}else{
						while($row = mysqli_fetch_array($result)){
							array_push($resultList,'"【'.$row['broadcast'].'】 '.$row['tv_title'].'"');
						}
						array_push($resultList,'"Home"');
						array_push($resultList,'"뒤로"');
						$tmp = implode(", ", $resultList);

						updateStatus('koen_watching','koen_watching_view');
						echo'{
								"message":
									{
										"text":" ⎛° ͜ʖ°⎞⎠  \n【# 국내예능】\n ┗이름으로검색\n======================\n▶예능 프로 제목을 선택하면 \n  상세정보가 보입니다.\n  각 회차로 이동 가능!  \n  ༼ つ ◕_◕ ༽つ "
									},
								"keyboard":
									{
										"type": "buttons",
										"buttons":['.$tmp.']
									}
							}';	

						break;
					}

				}else{
					echo'{"message":{"text":"올바르지 않은 값이에요"},"keyboard":{"type": "buttons","buttons":["Home"]}}';	
				}


				break;
			

			//========================================================================================================================================
			//========================================================================================================================================

			//예능 프로 이름 클릭했을 때, base테이블에 있는 정보와, 회차를 버튼으로 리턴함
			case 'koen_watching_view':
				if($content=='뒤로'){
					updateStatus('koen_watching_view','koen_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내예능】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n예능 프로 제목으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}else{
					$tmp = explode('】',$content);
					$tmp_broadcast = $tmp[0];
					$tmp_broadcast = str_replace('【','',$tmp_broadcast);
					$tmp_tv_title = $tmp[1];
					$tmp_tv_title = ltrim($tmp_tv_title);

					$tv_result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE broadcast = \"".$tmp_broadcast."\" AND tv_title = \"".$tmp_tv_title."\"");
					$tv_row = mysqli_fetch_array($tv_result);

					$tv_id = $tv_row['tv_id'];
					$tv_title = $tv_row['tv_title'];
					$genre = $tv_row['genre'];
					$grade = $tv_row['grade'];
					$broadcast = $tv_row['broadcast'];
					$term = $tv_row['term'];
					$highest_rating = $tv_row['highest_rating'];
					$summary = $tv_row['summary'];
					$preview = $tv_row['preview'];
					$actor = $tv_row['actor'];
					$homepage = $tv_row['homepage'];

					$tv_src_result = mysqli_query($conn, "SELECT * FROM tbl_tv_src WHERE tv_id = \"".$tv_id."\" ORDER BY broad_date");
					$tv_src_resultList = array();

					$term1 = mb_strcut($term, 0, 24);
					while($tv_src_row = mysqli_fetch_array($tv_src_result)){
						array_push($tv_src_resultList,'"【'.$broadcast.'】.'.$tv_title.'.'.$tv_src_row['tv_idx'].'화.'.$tv_src_row['broad_date'].'"');
					}

					array_push($tv_src_resultList,'"Home"');
					$tmp = implode(", ", $tv_src_resultList);

					updateStatus('koen_watching_view','koen_watching_view_src');
		            echo'{
		                  "message":
		                     {
		                          "text": "⎛° ͜ʖ°⎞⎠\n======================\n【'.$broadcast.'】.'.$tv_title.'\n======================\n▶개요\n'.$genre.' | '.$grade.'\n시청률 : '.$highest_rating.'\n'.$term1.' \n▶ 출연진 \n'.$actor.' \n▶ 줄거리 \n'.$summary.' \n▶ 홈페이지 link\n'.$homepage.'"
		                     },
		                  "keyboard":
		                     {
		                        "type": "buttons",
		                        "buttons":['.$tmp.']
		                     }
		               }';   				
				}
				break;
				
			//========================================================================================================================================
			//========================================================================================================================================
			//예능 이름 선택하고 나서 회차 선택했을 때, src테이블에 있는 정보와, 스트리밍 링크 리턴함
			case 'koen_watching_view_src':
				if($content=='뒤로'){
					updateStatus('koen_watching_view_src','koen_watching');
					echo'{
							"message":
								{
									"text":" ⎛° ͜ʖ°⎞⎠ \n【# 국내예능】\n======================\n▶ 전체목록(최신순)\n▶ 전체목록(가나다순)\n▶ 이름으로검색\n▶ Home\n======================\n첫 방송일 기준 최신순으로 정렬\n  ☞ 전체목록(최신순)\n제목을 기준 가나다순 정렬\n  ☞ 전체목록(가나다순)\n예능 프로 제목으로 검색\n  ☞ 이름으로검색"
								},
							"keyboard":
								{
									"type": "buttons",
									"buttons":["전체목록(최신순)","전체목록(가나다순)","이름으로검색","Home","뒤로"]
								}
						}';
				}else{
					// [KBS2].연애의 발견.1화.2014-08-18
					$tmp = explode('.',$content);

					$tmp_braodcast = $tmp[0];		// [KBS2]
					$tmp_broadcast = preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}\【\】]/i", "", $tmp_braodcast); // KBS2
					$tmp_tv_title = $tmp[1];		// 연애의 발견
					$tmp_tv_idx = $tmp[2];		// 1화
					$tmp_tv_idx = preg_replace("/[^0-9]*/s", "", $tmp_tv_idx); // 1
					$tmp_braod_date = $tmp[3];		// 2014-08-18

					//방송사와 제목으로 tv_id를 찾음
					$tv_result = mysqli_query($conn,"SELECT * FROM tbl_tv_base WHERE broadcast = \"".$tmp_broadcast."\" AND tv_title = \"".$tmp_tv_title."\"");
					$tv_row = mysqli_fetch_array($tv_result);
					$tv_id = $tv_row['tv_id'];

					//회차와 방송일자, tv_id로 tv_src테이블 조회
					$tv_info_result = mysqli_query($conn,"SELECT * FROM tbl_tv_src WHERE tv_idx = \"".$tmp_tv_idx."\" AND broad_date = \"".$tmp_braod_date."\" AND tv_id = \"".$tv_id."\"");
					$tv_info_row = mysqli_fetch_array($tv_info_result);
					$tv_src_id = $tv_info_row['tv_src_id'];
					$tv_src = $tv_info_row['tv_src'];
					$summary = $tv_info_row['summary'];

					//tv_id로 각 회차 정보를 찾음(방송일자로 정렬)
					$tv_src_result = mysqli_query($conn, "SELECT * FROM tbl_tv_src WHERE tv_src_id != ".$tv_src_id." AND tv_id = ".$tv_id." ORDER BY broad_date");
					$tv_src_resultList = array();

					// 2018-08-20 추가내용 : history 테이블 기록 기능 추가
					mysqli_query($conn, "INSERT INTO history VALUES((SELECT IFNULL(MAX(u.history_id),0)+1 FROM history u),NOW(),".$tv_src_id.",".$user_id.",NULL,\"".$content."\")");

					array_push($tv_src_resultList,'"▶ 【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tmp_tv_idx.'화.'.$tmp_braod_date.'"');
					while($tv_src_row = mysqli_fetch_array($tv_src_result)){
						array_push($tv_src_resultList,'"【'.$tmp_broadcast.'】.'.$tmp_tv_title.'.'.$tv_src_row['tv_idx'].'화.'.$tv_src_row['broad_date'].'"');
					}
					array_push($tv_src_resultList,'"Home"');
					array_push($tv_src_resultList,'"뒤로"');
					// array_push($tv_src_resultList,'"책갈피"');
					$tmp1 = implode(", ", $tv_src_resultList);
		            echo'{
		                  "message":
			                     {
			                          "text": "⎛° ͜ʖ°⎞⎠\n======================\n【'.$tmp_broadcast.'】.'.$tmp_tv_title.'\n======================\n▶ '.$tmp[2].' \n▶ 방송일자 : '.$tmp_braod_date.'\n▶ 줄거리 \n'.$summary.'",
					    			"message_button": {
						      			"label": "바로 시청하기",
						      			"url": "'.$tv_src.'"
						    			}
				    			},
							"keyboard":
								{
									"type": "buttons",
									"buttons":['.$tmp1.']
								}
						}';
				}
				break;

// ###########################################################################################################################################
			default:
				echo '{"message":{"text":"올바르지 않은 값이에요"},"keyboard":{"type": "text"}}';	
				break;
		}
	}

	function updateStatus($currstatus, $newstatus){
		global $conn,$user_id;
		mysqli_query($conn,"UPDATE tbl_users SET prev_status = \"".$currstatus."\", curr_status = \"".$newstatus."\" WHERE user_id = \"".$user_id."\"");
	}

	function logging($user_id,$requestMsg,$responseMsg){
		global $conn;
		mysqli_query($conn,"INSERT INTO tbl_logging VALUES((SELECT IFNULL(MAX(u.idx),0)+1 FROM tbl_logging u),\"".$user_id."\",NOW(),\"".$requestMsg."\",\"".$responseMsg."\")");
	}

?>
