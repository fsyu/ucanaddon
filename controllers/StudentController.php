<?php session_start();
class Power_StudentController extends Zend_Controller_Action
{
	protected $path='localhost/ccawww/public/power';
	protected $db;
	protected $user;
	protected $useragent;
	protected $advisory='[證照E諮詢單]';
	protected $assist='[assist]';
	protected $bkdata='[證照E學生帳戶]';
	protected $deptlicenses='[證照E單位認列證照]';
	protected $deptinfself='[證照E系級資訊自列]';
	protected $scholarship='[證照E獎學金申請]';
	protected $backtoapply='[證照E返回申請]';
	protected $bk_dir= 'C:/AppServ/www/ccawww/public/power/upload/bank';
	protected $bk_url= '../upload/bank/';
	protected $licenses_dir= 'C:/AppServ/www/ccawww/public/power/upload/licenses';
	protected $licenses_url= '../upload/licenses/';
	protected $scholarship_dir= 'C:/AppServ/www/ccawww/public/power/upload/scholarship';
	protected $scholarship_url= '../upload/scholarship/';
	protected $types_value = array('0','資訊證照', '專業證照','英文能力');
	protected $types_key = array('0','1', '2','3');

    public function init()
    {
        /* Initialize action controller here */
		require_once('My/Controller/Init/InitController.php');
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$this->user->ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->user->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$this->user->ip = $_SERVER['REMOTE_ADDR'];
		}
    }

    public function indexAction() {

    }
	
	public function studentindexAction() {
		$stuid = 'G040Q007';
		$advisory=$this->advisory;
		$deptinfself=$this->deptinfself;
		$deptlicenses=$this->deptlicenses;
		$scholarship=$this->scholarship;
		$backtoapply=$this->backtoapply;
		$assist=$this->assist;
		$bkdata=$this->bkdata;
		/////////////////////////學生個人資料/////////////////////////
		$SQLCommand2 = "SELECT * from  advedusys.dbo.View_學生清冊 as u where u.[學號]=? ;";
		$result2 = $this->msquery($SQLCommand2,array($stuid));
		$r2 = $result2['rows'];
		$this->user->name = $r2[0]['姓名'];
		$this->user->stuid = $r2[0]['學號'];
		$this->user->department = $r2[0]['科系名稱'];
		// $this->user->department_id =$r2[0]['科系代碼'];
		$this->user->department_id =35;
		$this->user->email = $r2[0]['電子信箱'];
		$this->user->tel = $r2[0]['聯絡電話'];
		$this->user->phone = $r2[0]['聯絡電話'];
		$this->user->user_id = $r2[0]['KSUID'];
		$this->user->school = $r2[0]['學部名稱'];
		$this->user->sex = $r2[0]['性別'];
		$this->user->class = $r2[0]['班級名稱'];
		$this->user->address = $r2[0]['戶籍地址'];
		$this->user->program = $r2[0]['學制名稱'];
		$this->user->twid = $r2[0]['身分證字號'];
		$this->view->user = $this->user;
		////////////////學生證照負責單位//////////////
		$this->unit->information=35;
		$this->unit->english = 97;
		$this->unit->career = 112;
		$this->unit->profession = $this->user->department_id;
		$this->view->unit =$this->unit;
		$this->view->licenses_url =$this->licenses_url;
		try{
			//ajax 傳回動作
			$do = $_GET["do"];
			if($do!=null){ 
				switch ($do) {
					//動態取出發照機構
					case 'getagency':
						$categories_id = $_GET["categories_id"];
						$SQLCommand = "SELECT * FROM [cca].[dbo].[v_edudb] as v where v.supervisor = ? ORDER BY name; ";
						$result = $this->msquery($SQLCommand,array($categories_id));
						$this->_helper->json->sendJson($result['rows']);
					break;
					//ajax 拉出提問
					case 'getadvisory':
						$source = $_GET["source"];//來源為管理端或前端
						$stu_id = $_GET["stu_id"];
						$unit_id = $_GET["unit_id"];//要詢問的單位
						$SQLCommand = "SELECT * from  [cca].[dbo].".$advisory."  where 學號 = ? and 單位ID = ? ORDER BY 發問時間 DESC; ";
						$result = $this->msquery($SQLCommand,array($stu_id,$unit_id));
						if($source=='student'){
							$SQLCommand1 = "UPDATE [cca].[dbo].".$advisory."  SET 讀取狀態 = 1 WHERE 學號 = ? and 回答內容 is not null";
							$this->msquery($SQLCommand1, array($stu_id));
						}
						$this->_helper->json->sendJson($result['rows']);
					break;
					//新增諮詢單
					case 'insertadvisory':
						$stu_id=$_GET['stu_id'];//學生學號
						$unit_id=$_GET['unit_id'];//詢問單位
						$question=$_GET['question'];//問題內容
						
						$question_time = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						$SQLCommand="INSERT INTO [cca].[dbo].".$advisory." ( 學號 , 單位ID , 問題 , 發問時間 , ip) VALUES ( ? , ? , ? , ? ,?);"; 
						$result = $this->msquery($SQLCommand,array($stu_id,$unit_id,$question,$question_time,$this->user->ip));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
					//返回我的能力申請
					case 'backtoapply':
						$assist_id=$_GET['assist_id'];//通報ID
						$types=$_GET['types'];//類別
						$status=$_GET['status'];//狀態
						$update_time = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						try {	
							$SQLCommand = "SELECT * FROM [cca].[dbo].".$backtoapply." where 學號 = ? and assist_id= ? ;";
							$Result = $this->msquery($SQLCommand,array($this->user->stuid,$assist_id)); //取出通報資料
							$rows = $Result['rows'];
						} catch (Zend_Db_Exception $e) {
							die($e->getMessage());
						}	
						//檢查是否第一次
						if(count($rows) == 0){
							//是第一次
							try {	
								$SQLCommand="INSERT INTO [cca].[dbo].".$backtoapply." ( 學號 , assist_id , 申請前類別 , 申請前狀態 , 申請時間 , 申請IP) VALUES ( ? , ? , ? , ? , ? , ?);"; 
								$result = $this->msquery($SQLCommand,array($this->user->stuid,$assist_id,$types,$status,$update_time,$this->user->ip));
								$this->_helper->json->sendJson(array('success'=>'true'));
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}							 
						} else if($Result['rows'][0]['審核狀態']!=4){
							//非第一次
							try {	
								$SQLCommand = "update cca.dbo.".$backtoapply." set 
											審核人 = ?,
											審核時間 = ?,
											審核狀態 = ?,
											申請IP = ?,
											審核IP = ?
											where 學號 = ? and assist_id= ?;";
								$Result = $this->msquery($SQLCommand,array('','','2',$this->user->ip,'',$this->user->stuid,$assist_id));
								$this->_helper->json->sendJson(array('success'=>'true'));
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}
						}	
					//如果當過去送過時該如何?
					break;	
					//ajax 送審證照
					case 'updatelicensestype':
						$types =$_GET["types"];
						$assist_id =$_GET["assist_id"];
						$SQLCommand = "update cca.dbo.[assist] set types = ? , status= ? where assist_id = ? ;";	
						$result = $this->msquery($SQLCommand,array($types,'2',$assist_id));
						$this->_helper->json->sendJson($result['rows']);
					break;	
					//ajax 拉出所有證照
					case 'getlicenses':
						$autoComplete =$_GET["autoComplete"];//所搜尋之關鍵字
						if( mb_strlen( $autoComplete, "utf-8")>=3){ //當關鍵字大於3個字以上才執行
							$SQLCommand = "SELECT edu.edudb_id,edu.name,edu.tcode,('技專代號：'+CAST (edu.tcode AS VARCHAR(10) )+',名稱：'+edu.name+(CASE WHEN edu.series is null THEN '' ELSE  '('+edu.series+')'  END )+',單位：'+edu.organization) as allitem FROM [cca].[dbo].[edudb] as edu where (edu.tcode like '%".$autoComplete."%' or edu.name like '%".$autoComplete."%' or edu.organization like '%".$autoComplete."%' )and edu.open_status='1';";
							$result = $this->msquery($SQLCommand,array());	
							$this->_helper->json->sendJson($result['rows']);
						}
					break;	
					//ajax 取得分行資料
					case 'getbkserial':
						$bk =$_GET["bk"];//銀行代碼
						$SQLCommand = "SELECT DISTINCT BKCODE,BKNAME FROM  CHACC_KSA.dbo.TBBK WHERE LEN(BKCODE) = 7 and BK= ?  ORDER BY BKCODE ";
						$result = $this->msquery($SQLCommand,array($bk));	
						$this->_helper->json->sendJson($result['rows']);
					break;
					//新增獎學金申請單
					case 'insertscholarship':
						$stu_id = $_GET["stu_id"];
						$assist_id=$_GET['assist_id'];//證照通報ID
						$bk=$_GET['bk'];//銀行代碼
						$bkcode=$_GET['bkcode'];//分行代碼
						$account=$_GET['account'];//銀行帳號
						$updata_time = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						try {	
							$SQLCommand = "SELECT * FROM [cca].[dbo].".$scholarship." where 學號 = ? and assist_id= ? ;";
							$Result = $this->msquery($SQLCommand,array($stu_id,$assist_id)); //取出通報資料
							$rows = $Result['rows'];
						} catch (Zend_Db_Exception $e) {
							die($e->getMessage());
						}	
						//檢查是否第一次
						if(count($rows) == 0){
							//是第一次
							try {	
								$SQLCommand="INSERT INTO [cca].[dbo].".$scholarship." ( 學號 , assist_id , 銀行代碼 , 分行代碼 , 銀行帳號, 時間 , ip) VALUES ( ? , ? , ? , ? ,? , ? , ?);"; 
								$result = $this->msquery($SQLCommand,array($stu_id,$assist_id,$bk,$bkcode,$account,$updata_time,$this->user->ip));
								if(!file_exists($this->scholarship_dir.'/'.$this->user->stuid.'-'.$bk.'-'.$bkcode.'-'.$account.'.jpg')){
										copy($this->bk_dir.'/'.$stu_id.'.jpg', $this->scholarship_dir.'/'.$this->user->stuid.'-'.$bk.'-'.$bkcode.'-'.$account.'.jpg');
								}
								$this->_helper->json->sendJson($result['rows']);
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}							 
						} else {
						 //非第一次
							$SQLCommand = "update cca.dbo.".$scholarship." set 
											銀行代碼 = ?,
											分行代碼 = ?,
											銀行帳號 = ?,
											時間 = ?,
											ip = ?
											where 學號 = ? and assist_id= ?;";
							try {	
								$Result = $this->msquery($SQLCommand,array($bk,$bkcode,$account,$updata_time,$this->user->ip,$stu_id,$assist_id));
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}
						}	
					break;	
					//修改銀行資料
					case 'updatebk':
						// $bk =$_GET["bk"];//銀行代碼
						// $serial =$_GET["serial"];//分行代碼
						// $account =$_GET["account"];//存簿帳號
						$bk =$this->_request->getPost("bk");//銀行代碼
						$serial =$this->_request->getPost("serial");//分行代碼
						$account =$this->_request->getPost("account");//存簿帳號
						//查詢是否原先已有資料
						$SQLCommand = "select * from cca.dbo.".$bkdata." 
										where 學號 = ? ;";
						try {	
							$Result = $this->msquery($SQLCommand,array($this->user->stuid)); //取出通報資料
							$rows = $Result['rows'];
						} catch (Zend_Db_Exception $e) {
							die($e->getMessage());
						}	
						$updatetime = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						//檢查是否第一次
						if(count($rows) == 0){
							//是第一次
							$SQLCommand="INSERT INTO [cca].[dbo].".$bkdata." ( 銀行代碼 , 分行代碼 , 學號 , 單位ID, 時間 , ip , 銀行帳戶 ) VALUES ( ? , ? , ? , ? , ? , ? , ? );"; 
							try {	
								$Result = $this->msquery($SQLCommand,array($bk,$serial,$this->user->stuid,$this->user->department_id,
																$updatetime,$this->user->ip,$account));	
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}							 
						} else {
						 //非第一次
							$SQLCommand = "update cca.dbo.".$bkdata." set 
											銀行代碼 = ?,
											分行代碼 = ?,
											單位ID = ?,
											時間 = ?,
											ip = ?,
											銀行帳戶 = ?
											where 學號 = ? ;";	
							//$this->view->mss = $SQLCommand; 
							try {	
								$Result = $this->msquery($SQLCommand,array($bk,$serial,$this->user->department_id,$updatetime,$this->user->ip,
														$account,$this->user->stuid));
								$this->_helper->json->sendJson($Result['rows']);						
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}
						}
						//圖片上傳程式碼
						$upload_dir=$this->bk_dir;
						$cunbupic = $_FILES["cunbupic"]["name"]; // 檔案名稱
						//$output='.png';
						//imagepng(imagecreatefromstring(file_get_contents($cunbupic)),output);
						$ext = pathinfo($cunbupic, PATHINFO_EXTENSION); // 副檔名
						if($licensesimg==""||$ext==""){
						}else{
							if($ext=='png'||$ext=='jpg'||$ext=='gif'||$ext=='jpeg'){
								//建立上傳檔案的位置
								$new_pic_name = $this->user->stuid.'.'.$ext;
								echo '新檔名'.$new_pic_name.'<br/>';
								$uploadpic = $upload_dir."/".$new_pic_name;	

								// 複製檔案 (原來的檔案型態)										
								if (move_uploaded_file($_FILES['licensesimg']['tmp_name'],   iconv("UTF-8", "big5", $uploadpic ) )) {
									//假如成功，轉檔成為 jpg

								} else {
									echo "上傳失敗 \n";
								}										

								$originalImage = $uploadpic;
								if (preg_match('/jpg|jpeg/i',$ext)){
									$imageTmp=imagecreatefromjpeg($originalImage);
								}else if (preg_match('/png/i',$ext)){
									
									$imageTmp=imagecreatefrompng($originalImage);
								}else if (preg_match('/gif/i',$ext))
									$imageTmp=imagecreatefromgif($originalImage);
								else if (preg_match('/bmp/i',$ext))
									$imageTmp=imagecreatefrombmp($originalImage);
								else
									return 0;
								// quality is a value from 0 (worst) to 100 (best)
								$quality = 75;
								$outputImage = $this->stuser->stuid.'-'.$tcode.'.jpg';
								
								$newoutputpic = $upload_dir."/".$outputImage;
								imagejpeg($imageTmp, $newoutputpic, $quality);
								//imagedestroy($imageTmp);
							}
							print_r($_FILES);
						}
						$this->_redirect('power/student/studentindex');
					break;
					//ajax 通報證照
					case 'insertassist':
						$mytcode =$_GET["mytcode"];//證照代號
						$myissue =$_GET["myissue"];//證照發照日
						//查詢是否存在assist	
						$SQLCommand = "select * from cca.dbo.".$assist." 
										where ksuid = ? 
										and stuid = ? 
										and  tcode = ?;";
						try {	
							$Result = $this->msquery($SQLCommand,array($this->user->user_id,$this->user->stuid,$mytcode)); //取出通報資料
							$rows = $Result['rows'];
							//$this->view->debugLog(count($rows),1);	
							
						} catch (Zend_Db_Exception $e) {
							die($e->getMessage());
						}	
						//檢查是否第一次通報
						if(count($rows) == 0){
							//是第一次通報
							$SQLCommand = "insert into cca.dbo.".$assist." (years,semester,name,ksuid,stuid,tcode,issue,lesson,gender,
											month,department,program,ip)										
											select
											YEAR(DATEADD(month, -7, getdate())) -1911,
											CASE WHEN MONTH(GETDATE()) in (1,8,9,10,11,12)
											then 1 else 2 END,
											?,?,?,?,?,?,?,
												MONTH(GETDATE()),?,?,?
											from cca.dbo.edudb e where e.tcode = ?";
							try {	
								$Result = $this->msquery($SQLCommand,array($this->user->name,$this->user->user_id,$this->user->stuid,$mytcode,$myissue,
																	$this->user->class,$this->user->sex,$this->user->department,$this->user->school,$this->user->ip,$mytcode)
																);		
								$this->_helper->json->sendJson($Result['rows']);
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}							 
						} else {
						 //非第一次通報
							try {	
								$SQLCommand = "update cca.dbo.".$assist." set 
										lesson = ?,
										issue = ?,
										ip = ?,
										user_id = ?,
										updated = getdate()
										where stuid = ?
											and ksuid= ? 
											and  tcode = ? 
											and  status <= 2
										;";	
								$Result = $this->msquery($SQLCommand,array($this->user->class,$myissue,$this->user->ip,$this->user->user_id,$this->user->stuid,$this->user->user_id,$mytcode));
								$this->_helper->json->sendJson($Result['rows']);
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}	
							$premise = '該證照重覆通報!僅補寄及變更日期';
						}		
					break;
					default:
					break;
				}
			//預設之資料
			}else{
				$this->view->types_value=$this->types_value;
				$this->view->types_key=$this->types_key;
				////////////////////////學生銀行資料//////////////////////
				$stBKSQLCommand = "SELECT * FROM [cca].[dbo].[證照E學生帳戶] as bkac 
									left join CHACC_KSA.dbo.TBBK as b
									on b.BK = bkac.[銀行代碼]
									left join advedusys.dbo.[證照_銀行代碼表] as bk
									on bk.[銀行代碼] = bkac.[銀行代碼]
									where bkac.[分行代碼]= b.BKCODE and 
									bkac.[學號]= ? ;";		
				$stbkresult =$this->msquery($stBKSQLCommand, array($stuid));
					/////////////////////////存簿圖片/////////////////////////
				$stbkresult['rows'][0]['圖片狀態']=0;	
				if(file_exists($this->bk_dir.'/'.$this->user->stuid.'.jpg')){
					$stbkresult['rows'][0]['圖片狀態']=1;
				}
				$stbk = $stbkresult['rows'];
				$ststatus=1;
				if($stbk[0]['BK']==null||$stbk[0]['BKCODE']==null||$stbk[0]['圖片狀態']==0||$stbk[0]['銀行帳戶']==null){
					$ststatus=0;
				}
				$this->view->stbk = $stbk;
				$this->view->ststatus = $ststatus;
				$this->view->bk_url =$this->bk_url;
				/////////////////////////銀行資料/////////////////////////
				$BKSQLCommand = "SELECT * FROM advedusys.dbo.[證照_銀行代碼表];";		
				$bkresult =$this->msquery($BKSQLCommand, array());	
				$bk = $bkresult['rows'];
				$this->view->bkdata = $bk;
				/////////////////////////檢查該系級該學年資訊是否依照電算中心/////////////////////////
				$statussql = "SELECT dlic.[認列狀態] FROM [cca].[dbo].[證照E學年] as years 
									left join ( 
									SELECT d1.dept_id,d1.認列狀態,d1.學年 
									FROM [cca].[dbo].".$deptinfself." as d1 WHERE d1.時間 = ( 
										SELECT MAX(d2.時間) 
										FROM [cca].[dbo].".$deptinfself."as d2 
										WHERE d2.dept_id = d1.dept_id and d1.學年 = d2.學年 
										GROUP BY d2.dept_id 
									)
									) as dlic on dlic.[學年]=years.[學年] where dlic.dept_id= ? and dlic.[學年]= ? ;";
				$statusresult = $this->msquery($statussql,array($this->user->department_id,'105'));
				$status = $statusresult['rows'][0]['認列狀態'];
				if($status==1){
					$infseachdept=$this->user->department_id;
				}else{
					$infseachdept=$this->unit->information;
				}

				/////////////////////////我的證照/////////////////////////
				$SQLCommand = "SELECT a.assist_id,a.edudb_id,a.department,a.ksuid,a.stuid,a.u_name,a.created,a.tcode,a.cername,a.issue,a.ctype,
				a.category,a.series,a.organization,a.gender,a.equivalent,a.s_name,a.status,資訊狀態,專業狀態,s.狀態 as 獎學金狀態
				from [cca].[dbo].[v_assist] as a 
				LEFT JOIN [cca].[dbo].".$scholarship." as s on s.assist_id =a.assist_id
				LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態 as 專業狀態
							FROM [cca].[dbo].".$deptlicenses." as deptl 
							where deptl.時間 = ( 
								SELECT MAX(deptl2.時間) 
									FROM [cca].[dbo].".$deptlicenses." as deptl2 
									WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別]  and deptl2.[類別] = ".$this->types_key[2]."  and deptl2.dept_id=35 and deptl2.[學年]=105
									GROUP BY deptl2.edudb_id )) as vpl on vpl.edudb_id =a.edudb_id
				LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態  as 資訊狀態 
							FROM [cca].[dbo].".$deptlicenses." as deptl 
							where deptl.時間 = ( 
								SELECT MAX(deptl2.時間) 
									FROM [cca].[dbo].".$deptlicenses." as deptl2 
									WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別]  and deptl2.[類別] = ".$this->types_key[1]."  and deptl2.dept_id= ? and deptl2.[學年]=105
									GROUP BY deptl2.edudb_id )) as vil on vil.edudb_id =a.edudb_id 
				where a.stuid =  ?  and (a.status='1' or a.status='0')";	//單位後續需傳進來	
				$result =$this->msquery($SQLCommand, array($infseachdept,$stuid));	
				$r = $result['rows'];
				//取出證照圖片有無狀態
				$count = count($r);
					for($i=0;$i<$count;$i++){
						$r[$i]['圖片狀態']=0;
						if(file_exists($this->licenses_dir.'/'.$this->user->stuid.'-'.$r[$i]['tcode'].'.jpg')){
							$r[$i]['圖片狀態']=1;
						}
						
					}
				$this->view->data = $r;
				/////////////////////////資訊證照/////////////////////////
				$SQLCommand1 = "SELECT a.assist_id,a.edudb_id,a.department,a.ksuid,a.stuid,a.u_name,a.created,a.tcode,a.cername,a.issue,a.ctype,
				a.category,a.series,a.organization,a.gender,a.equivalent,a.s_name,a.status,a.comment,a.types,back.[審核狀態] as 返回狀態  ,back.[原因] , t.證照類別項目ID,t.類別名稱
				from [cca].[dbo].[v_assist] as a 
				LEFT JOIN [cca].[dbo].".$backtoapply." as back on a.assist_id = back.assist_id 
				LEFT JOIN [cca].[dbo].[證照E證照類別] as t on t.證照類別項目ID = a.types
				LEFT JOIN status as c on a.status = c.status_id 
				where a.stuid = ? and a.status BETWEEN 2 and 8 and a.types = ".$this->types_key[1]." ;";		
				$result1 =$this->msquery($SQLCommand1, array($stuid));	
				$r1 = $result1['rows'];
				//取出證照圖片有無狀態
				$count = count($r1);
					for($i=0;$i<$count;$i++){
						$r1[$i]['圖片狀態']=0;
						if(file_exists($this->licenses_dir.'/'.$this->user->stuid.'-'.$r1[$i]['tcode'].'.jpg')){
							$r1[$i]['圖片狀態']=1;
						}
						
					}	
				$this->view->infdata = $r1;
				/////////////////////////專業證照/////////////////////////
				$SQLCommand2 = "SELECT a.assist_id,a.edudb_id,a.department,a.ksuid,a.stuid,a.u_name,a.created,a.tcode,a.cername,a.issue,a.ctype,
				a.category,a.series,a.organization,a.gender,a.equivalent,a.s_name,a.comment,a.status ,back.[審核狀態] as 返回狀態,back.[原因] ,t.證照類別項目ID,t.類別名稱
				from [cca].[dbo].[v_assist] as a 
				LEFT JOIN [cca].[dbo].".$backtoapply." as back on a.assist_id = back.assist_id 
				LEFT JOIN status as c on a.status = c.status_id
				LEFT JOIN [cca].[dbo].[證照E證照類別] as t on t.證照類別項目ID = a.types
				where a.stuid = ? and a.status BETWEEN 2 and 8 and a.types = ".$this->types_key[2]." ;";		
				$result2 =$this->msquery($SQLCommand2, array($stuid));	
				$r2 = $result2['rows'];
				//取出證照圖片有無狀態
				$count = count($r2);
					for($i=0;$i<$count;$i++){
						$rㄉ[$i]['圖片狀態']=0;
						if(file_exists($this->licenses_dir.'/'.$this->user->stuid.'-'.$r2[$i]['tcode'].'.jpg')){
							$r2[$i]['圖片狀態']=1;
						}
						
					}	
				$this->view->prodata = $r2;
				/////////////////////////問題數量依據學生未讀數量與回覆不為空填答(使用於有多少則新訊息)(資訊)/////////////////////////
				$advSQLCommand = "SELECT count(case when 單位ID = ?  then 1 end) as infcount,count(case when 單位ID = ?   then 1 end) as engcount ,count(case when 單位ID = ? then 1 end) as procount from [cca].[dbo].".$advisory." where 學號 = ? and 讀取狀態=0 and 回答內容 is not null";
				$advSQLresult = $this->msquery($advSQLCommand,array($this->unit->information,$this->unit->english,$this->unit->profession,$stuid));
				$advCount = $advSQLresult['rows'];
				$this->view->advcount = $advCount;
				
				/////////////////////////證照分類起始值/////////////////////////
				$categoriesSQLCommand = "SELECT * FROM [cca].[dbo].[v_edudb] as v where v.supervisor = '0' ORDER BY name;";
				$categoriesSQLresult = $this->msquery($categoriesSQLCommand,array());
				$categories = $categoriesSQLresult['rows'];
				$this->view->categories = $categories;
			}
		}catch(Zend_Db_Exception $e){
			echo $e->getMessage();
			// 設定 savesuccess.phtml 需要使用的訊息
			$this->view->messages = $this->_helper->getHelper('FlashMessenger')->getMessages();
		}
    }
	
	

	
	public function msquery($SQLCommand,$params) {
		try {
			$this->view->debugLog($SQLCommand,1);
			//$this->view->debugLog($params,1);
			
			$Result = sqlsrv_query($this->msdb,"$SQLCommand",$params) or die(sqlsrv_errors());
						
			if( $Result === false  ) {
				$error = sqlsrv_errors();					
				$this->view->message = mb_convert_encoding($error[0]['message'],"UTF-8","BIG5") . $SqlCommand; 
			}
			
			$values = array();	
			$rows = array();
			$DNull = true;
			
			while($row = sqlsrv_fetch_array($Result)) {	
			
				$DNull = false;	// has data!		
				$fields = array();
				$value = array();
				$kvalue = array();
				foreach($row as $key_big5=>$v) {
					if (!is_int($key_big5)) {
						$key_utf8 = mb_convert_encoding($key_big5,"UTF-8", "BIG5");
						//$rows[$key_utf8] = $row[$key_big5];
						//if ($transform) {
						//	$fields[] = $schema[$table2][$key_utf8]; //英文欄位
						//} else {	
							$fields[] = $key_utf8; //中文欄位 MS command using UTF8, value using BIG5
						//}

						if (is_string($v)) {
							$temp =  addslashes(str_replace("'", "", $v));
						} elseif (is_numeric($v)) {
							$temp = "$v";
						/* } elseif (is_float($v)) {
							$this->view->debugLog($v,1);
							$temp = "$v"; */
						} elseif (is_null($v)) {
							$temp = null;
						} else {
							$temp = date_format($v, 'Y-m-d H:i:s') ;
						}					
						$value[] = $temp;
						$kvalue[$key_utf8] = $temp;
					}
				}
				$values[] = $value;	
				//$this->view->debugLog($kvalue,1);
				$rows[] = $kvalue;				
			}				
		} catch (Exception $e) {
			$result['code'] = '99';
			$result['text'] = $e->getMessage();
			$this->view->message = $e->getMessage();
			$this->view->debugLog($e->getMessage(),1);				
		}
	
		return array(
				'results'=>array('isNull'=>$DNull,'result'=>$result),
				'fields' =>$fields,
				'values' => $values,
				'rows' => $rows
				);
	} 			
}
