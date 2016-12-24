<?php session_start();
class Power_AdminController extends Zend_Controller_Action
{
	protected $path='localhost/ccawww/public/power';
	protected $db;
	protected $user;
	protected $stuser;
	protected $useragent;
	protected $deptlicenses='[證照E單位認列證照]';
	protected $deptinfself='[證照E系級資訊自列]';
	protected $advisory='[證照E諮詢單]';
	protected $licensesgroup='[證照E群組]';
	protected $grouplist='[證照R群組明細]';
	protected $assist='[assist]';
	protected $bkdata='[證照E學生帳戶]';
	protected $scholarship='[證照E獎學金申請]';
	protected $backtoapply='[證照E返回申請]';
	protected $licenses_dir= 'C:/AppServ/www/ccawww/public/power/upload/licenses';
	protected $licenses_url= '../upload/licenses/';
	protected $types_value = array('0','資訊證照', '專業證照','英文能力');
	protected $types_key = array('0','1', '2','3');
    public function init()
    {
        /* Initialize action controller here */
		require_once('My/Controller/Init/InitController.php');
		$this->user->name = '游峰碩';
		$this->user->user_id = 'T093000300';
		$this->user->department = '電子計算機中心';
		$this->user->department_id ='35';
		$this->user->email = 'abc@yahoo.com.tw';
		$this->user->role = '證照審查人員';
		$this->user->fulltime = '資訊證照測試';
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
	
	//證照審核
	public function adminadvancedAction() {
		$this->view->types_value=$this->types_value;
		$this->view->types_key=$this->types_key;
		$this->view->licenses_url =$this->licenses_url;
		$deptinfself=$this->deptinfself;
		$deptlicenses=$this->deptlicenses;
		$scholarship=$this->scholarship;
		$backtoapply=$this->backtoapply;
		$assist=$this->assist;
		/////////////////////////檢查腳色之管理權限/////////////////////////
		if($this->user->role =='證照審查人員'){
			//取出該人員所負責項目
			switch (mb_substr( $this->user->fulltime , 0 , 2,"utf-8")) {
				case '英文':
					$types='3';
				break;
				case '專業':
					$types='2';
				break;
				case '資訊':
					$types='1';
				break;
				default:
					$types='0';
				break;
			}
			$this->view->admintypes=$types;
			if($types!=0){
				$do = $_GET["do"];
				if($do==null){
					$do = $_POST["do"];
				}
				if($do!=null){
					switch($do){
						//修改抵免審查狀態
						case 'updatestatus':
							$statusvalue=$_GET['statusvalue'];
							$assist_id=$_GET['assist_id'];
							$reason=$_GET['reason'];
							try {	
								$Result = $this->msquery("update cca.dbo.".$assist." set status = ?, comment = ?, user_id = ? where assist_id = ? ;",array($statusvalue,$reason,$this->user->user_id,$assist_id)
																);																		
								$this->_helper->json->sendJson($Result['rows']);
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}		
						break;
						//將證照退回我的能力區
						case 'updateback':
							$assist_id=$_GET['assist_id'];
							$statusvalue=$_GET['statusvalue'];
							$reason=$_GET['reason'];
							$update_time = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
							try {	
								$Result = $this->msquery("update cca.dbo.".$backtoapply." set 審核狀態 = ?,原因=?,審核IP= ?,審核人=?,審核時間=? where assist_id = ? ;",array($statusvalue,$reason,$this->user->ip,$this->user->user_id,$update_time,$assist_id));	
								if($Result['rows']='1'&&$statusvalue==7){
									$Result2 = $this->msquery("update cca.dbo.".$assist." set status = ?,types=? where assist_id = ? ;",array('0','',$assist_id));
									$this->_helper->json->sendJson($Result2['rows']);
								}
									$this->_helper->json->sendJson($Result['rows']);
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}		
						break;
						//修改證照日期
						case 'updatedate':
							$assist_id=$_GET['assist_id'];
							$date=$_GET['date'];
							try {	
								$Result = $this->msquery("update cca.dbo.".$assist." set issue = ?  where assist_id = ? ;",array($date,$assist_id));																		
								$this->_helper->json->sendJson($Result['rows']);
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}		
						break;
						//修改證照圖片
						case 'updateimg':
							//圖片上傳程式碼
							$upload_dir=$this->licenses_dir;
							$tcode=$_POST['tcode'];
							$stuid=$_POST['stuid'];
							$bookmark=$_POST['bookmark'];
							$licensesimg = $_FILES["licensesimg"]["name"]; // 檔案名稱
							$ext = pathinfo($licensesimg, PATHINFO_EXTENSION); // 副檔名
							if($licensesimg==""||$ext==""){
							}else{
								if($ext=='png'||$ext=='jpg'||$ext=='gif'||$ext=='jpeg'){
									//建立上傳檔案的位置
									$new_pic_name = $stuid.'-'.$tcode.'.'.$ext;
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
							$this->_redirect('power/admin/adminadvanced?bookmark='.$bookmark.'&getstuid='.$stuid);
						break;
					}
				}else{
					//取得目前頁籤所要執行動作
					$bookmark = $_GET["bookmark"];
					switch ($bookmark) {
						case 'search':
							$searchvalue = $_GET['searchvalue'];
							if($searchvalue!=null){
								/////////////////////////取出搜尋同學之資料///////////////////////////
								$result =$this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] from [cca].[dbo].[v_assist] as a 
									LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as s ON  s.[學號]=a.stuid
									WHERE( a.u_name like '%".$searchvalue."%' OR a.ksuid like '%".$searchvalue."%' OR a.stuid like '%".$searchvalue."%' OR s.[班級名稱] like '%".$searchvalue."%')
									GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] ORDER BY a.ksuid ASC", array($this->user->department_id,$types,$years));	
								$search = $result['rows'];
								$this->view->studata=$search;
								$this->view->searchvalue =$searchvalue;
							}	
						break;
						case 're-examination':
							//取出再審證照省略掉已通過與抵免的名單
							try {	
							$reresult = $this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] FROM [cca].[dbo].[v_assist] AS a
								LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 AS s ON  s.[學號]=a.stuid
								LEFT JOIN (SELECT a2.stuid FROM [cca].[dbo].[v_assist] AS a2 WHERE (a2.status=7 or a2.status=3) AND a2.types=? GROUP BY a2.stuid) AS a3 ON a3.stuid=a.stuid 
								WHERE a.status=5 AND a3.stuid IS NULL
								GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱]
								ORDER BY a.ksuid ASC",array($types));
							$rerows = $reresult['rows'];
							$this->view->studata=$rerows;
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}	
						break;
						case 'fail':
							//取出再審證照省略掉已通過的名單
							try {	
							$reresult = $this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] FROM [cca].[dbo].[v_assist] AS a
								LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 AS s ON  s.[學號]=a.stuid
								LEFT JOIN (SELECT a2.stuid FROM [cca].[dbo].[v_assist] AS a2 WHERE (a2.status=7 or a2.status=3) AND a2.types=? GROUP BY a2.stuid) AS a3 ON a3.stuid=a.stuid 
								WHERE a.status=4 AND a3.stuid IS NULL
								GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱]
								ORDER BY a.ksuid ASC",array($types));
							$rerows = $reresult['rows'];
							$this->view->studata=$rerows;
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}
						break;
						case 'backtoapply':
							try {	
							$backstresult = $this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱],a.cername,a.assist_id from [cca].[dbo].[v_assist] as a 
								LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as s ON  s.[學號]=a.stuid
								LEFT JOIN [dbo].[證照E返回申請] as e ON  e.assist_id=a.assist_id
								LEFT JOIN (SELECT a2.stuid FROM [cca].[dbo].[v_assist] AS a2 WHERE (a2.status=7 or a2.status=3) AND a2.types=? GROUP BY a2.stuid) AS a3 ON a3.stuid=a.stuid 
								WHERE e.[審核狀態]= ? AND a3.stuid IS NULL AND  e.[申請前類別]=?  GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱],a.cername,a.assist_id ORDER BY a.ksuid ASC",array($types,'2',$types));
							$backstresultrows = $backstresult['rows'];
							$this->view->studata=$backstresultrows;
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}	
						break;
						default:
							//取出送審之證照
							try {	
							$stresult = $this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] from [cca].[dbo].[v_assist] as a 
											LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as s ON  s.[學號]=a.stuid
											WHERE a.status=2 AND a.types=?  GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] ORDER BY a.ksuid ASC",array($types));
							$stresultrows = $stresult['rows'];
							$this->view->studata=$stresultrows;
							} catch (Zend_Db_Exception $e) {
								die($e->getMessage());
							}	
						break;
					}
					$getstuid = $_GET["getstuid"];
					if($getstuid!=null){
						try {	
							/////////////////////////學生個人資料/////////////////////////
							$SQLCommand2 = "SELECT * from  advedusys.dbo.View_學生清冊_輕量版 as u where u.[學號]= ? or u.KSUID = ? ;";
							
							$result2 = $this->msquery($SQLCommand2,array($getstuid,$getstuid));
							$r2 = $result2['rows'];
						}catch (Zend_Db_Exception $e) {
							die($e->getMessage());
						}
						$this->stuser->name = $r2[0]['姓名'];
						$this->stuser->enname = $r2[0]['英文姓名'];
						$this->stuser->stuid = $r2[0]['學號'];
						$this->stuser->department = $r2[0]['科系名稱'];
						$this->stuser->department_id =$r2[0]['科系代碼'];
						// $this->stuser->department_id =35;
						$this->stuser->email = $r2[0]['電子信箱'];
						$this->stuser->tel = $r2[0]['行動電話'];
						$this->stuser->user_id = $r2[0]['KSUID'];
						$this->stuser->school = $r2[0]['學部名稱'];
						$this->stuser->sex = $r2[0]['性別'];
						$this->stuser->class = $r2[0]['班級名稱'];
						$this->stuser->twid = $r2[0]['身分證字號'];
						$this->view->stuser = $this->stuser;
						////////////////學生資訊證照負責單位//////////////
						$this->unit->information=35;
						$admintype = $_GET["admintype"];
						if($admintype==null){$admintype=$types;}
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
						$statusresult = $this->msquery($statussql,array($this->stuser->department_id,'105'));
						$status = $statusresult['rows'][0]['認列狀態'];
						if($status==1){
							$infseachdept=$this->stuser->department_id;
						}else{
							$infseachdept=$this->unit->information;
						}
						switch ($admintype) {
							case '1':
								/////////////////////////資訊證照/////////////////////////
								$SQLCommand1 = "SELECT a.assist_id,a.edudb_id,a.department,a.ksuid,a.stuid,a.u_name,a.created,a.tcode,a.cername,a.issue,a.ctype,
								a.category,a.series,a.organization,a.gender,a.equivalent,a.s_name,a.status,a.comment,a.types,back.[審核狀態] as 返回狀態  , t.證照類別項目ID,t.類別名稱,專業狀態,資訊狀態
								from [cca].[dbo].[v_assist] as a 
								LEFT JOIN [cca].[dbo].".$backtoapply." as back on a.assist_id = back.assist_id 
								LEFT JOIN [cca].[dbo].[證照E證照類別] as t on t.證照類別項目ID = a.types
								LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態 as 專業狀態
									FROM [cca].[dbo].".$deptlicenses." as deptl 
									where deptl.時間 = ( 
										SELECT MAX(deptl2.時間) 
											FROM [cca].[dbo].".$deptlicenses." as deptl2 
											WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[2]."  and deptl2.dept_id=35 and deptl2.[學年]=105
											GROUP BY deptl2.edudb_id )) as vpl on vpl.edudb_id =a.edudb_id
								LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態  as 資訊狀態 
									FROM [cca].[dbo].".$deptlicenses." as deptl 
									where deptl.時間 = ( 
										SELECT MAX(deptl2.時間) 
											FROM [cca].[dbo].".$deptlicenses." as deptl2 
											WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[1]."  and deptl2.dept_id= ? and deptl2.[學年]=105
											GROUP BY deptl2.edudb_id )) as vil on vil.edudb_id =a.edudb_id 
								where a.stuid = ? and a.status BETWEEN 2 and 8 and a.types = ".$this->types_key[1]." ;";		
								$result1 =$this->msquery($SQLCommand1, array($infseachdept,$this->stuser->stuid));	
								$r1 = $result1['rows'];
								//取出證照圖片有無狀態
								$count = count($r1);
									for($i=0;$i<$count;$i++){
										$r1[$i]['圖片狀態']=0;
										if(file_exists($this->licenses_dir.'/'.$this->stuser->stuid.'-'.$r1[$i]['tcode'].'.jpg')){
											$r1[$i]['圖片狀態']=1;
										}
										
									}	
								$this->view->assistdata = $r1;
							break;
							case '2':
								
								/////////////////////////專業證照/////////////////////////
								$SQLCommand2 = "SELECT a.assist_id,a.edudb_id,a.department,a.ksuid,a.stuid,a.u_name,a.created,a.tcode,a.cername,a.issue,a.ctype,
								a.category,a.series,a.organization,a.gender,a.equivalent,a.s_name,a.comment,a.status ,t.證照類別項目ID,t.類別名稱,專業狀態,資訊狀態
								from [cca].[dbo].[v_assist] as a 
												LEFT JOIN edudb as b on a.tcode = b.tcode
												LEFT JOIN status as c on a.status = c.status_id
												LEFT JOIN [cca].[dbo].[證照E證照類別] as t on t.證照類別項目ID = a.types
												LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態 as 專業狀態
													FROM [cca].[dbo].".$deptlicenses." as deptl 
													where deptl.時間 = ( 
														SELECT MAX(deptl2.時間) 
															FROM [cca].[dbo].".$deptlicenses." as deptl2 
															WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[2]."  and deptl2.dept_id=35 and deptl2.[學年]=105
															GROUP BY deptl2.edudb_id )) as vpl on vpl.edudb_id =a.edudb_id
												LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態  as 資訊狀態 
													FROM [cca].[dbo].".$deptlicenses." as deptl 
													where deptl.時間 = ( 
														SELECT MAX(deptl2.時間) 
															FROM [cca].[dbo].".$deptlicenses." as deptl2 
															WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[1]."  and deptl2.dept_id= ? and deptl2.[學年]=105
															GROUP BY deptl2.edudb_id )) as vil on vil.edudb_id =a.edudb_id 
												where a.stuid = ? and a.status BETWEEN 2 and 8 and a.types = ".$this->types_key[2]." ;";		
								$result2 =$this->msquery($SQLCommand2, array($infseachdept,$this->stuser->stuid));	
								$r2 = $result2['rows'];
								//取出證照圖片有無狀態
								$count = count($r2);
									for($i=0;$i<$count;$i++){
										$rㄉ[$i]['圖片狀態']=0;
										if(file_exists($this->licenses_dir.'/'.$this->stuser->stuid.'-'.$r2[$i]['tcode'].'.jpg')){
											$r2[$i]['圖片狀態']=1;
										}
										
									}	
								$this->view->assistdata = $r2;
							break;
							case '3':

							break;
							case '0':
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
													WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[2]."  and deptl2.dept_id=35 and deptl2.[學年]=105
													GROUP BY deptl2.edudb_id )) as vpl on vpl.edudb_id =a.edudb_id
								LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態  as 資訊狀態 
											FROM [cca].[dbo].".$deptlicenses." as deptl 
											where deptl.時間 = ( 
												SELECT MAX(deptl2.時間) 
													FROM [cca].[dbo].".$deptlicenses." as deptl2 
													WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[1]."  and deptl2.dept_id= ? and deptl2.[學年]=105
													GROUP BY deptl2.edudb_id )) as vil on vil.edudb_id =a.edudb_id 
								where a.stuid =  ?  and (a.status='1' or a.status='0')";	//單位後續需傳進來	
								$result =$this->msquery($SQLCommand, array($infseachdept,$this->stuser->stuid));	
								$r = $result['rows'];
								//取出證照圖片有無狀態
								$count = count($r);
									for($i=0;$i<$count;$i++){
										$r[$i]['圖片狀態']=0;
										if(file_exists($this->licenses_dir.'/'.$this->stuser->stuid.'-'.$r[$i]['tcode'].'.jpg')){
											$r[$i]['圖片狀態']=1;
										}
										
									}
								$this->view->assistdata = $r;
							break;
						}
					}
				}
			}
		}
	}
//證照審核
	public function adminapplyeduebAction() {
		$do = $_GET["do"];
		if($do==null){
			$do = $_POST["do"];
		}
		if($do!=null){
			switch($do){
				
				//修改證照日期
				case 'updatedate':
					$assist_id=$_GET['assist_id'];
					$date=$_GET['date'];
					try {	
						$Result = $this->msquery("update cca.dbo.".$assist." set issue = ?  where assist_id = ? ;",array($date,$assist_id));																		
						$this->_helper->json->sendJson($Result['rows']);
					} catch (Zend_Db_Exception $e) {
						die($e->getMessage());
					}		
				break;
			}
		}else{
			//取得目前頁籤所要執行動作
			$bookmark = $_GET["bookmark"];
			if($bookmark==null){$bookmark='general';}
			switch ($bookmark) {
				case 'general':
					$backstresult = $this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱],a.cername,a.assist_id from [cca].[dbo].[v_assist] as a 
						LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as s ON  s.[學號]=a.stuid
						LEFT JOIN [dbo].[證照E返回申請] as e ON  e.assist_id=a.assist_id
						LEFT JOIN (SELECT a2.stuid FROM [cca].[dbo].[v_assist] AS a2 WHERE (a2.status=7 or a2.status=3) AND a2.types=? GROUP BY a2.stuid) AS a3 ON a3.stuid=a.stuid 
						WHERE e.[審核狀態]= ? AND a3.stuid IS NULL AND  e.[申請前類別]=?  GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱],a.cername,a.assist_id ORDER BY a.ksuid ASC",array($types,'2',$types));
					$backstresultrows = $backstresult['rows'];
					$this->view->studata=$backstresultrows;
				break;
				default:
					//取出送審之證照
					try {	
					$stresult = $this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] from [cca].[dbo].[v_assist] as a 
									LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as s ON  s.[學號]=a.stuid
									WHERE a.status=2 AND a.types=?  GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] ORDER BY a.ksuid ASC",array($types));
					$stresultrows = $stresult['rows'];
					$this->view->studata=$stresultrows;
					} catch (Zend_Db_Exception $e) {
						die($e->getMessage());
					}	
				break;
			}
		}
	}

	//(上傳/通報)
	public function adminbasisAction() {
		$this->view->types_value=$this->types_value;
		$this->view->types_key=$this->types_key;
		$advisory=$this->advisory;
		$deptinfself=$this->deptinfself;
		$deptlicenses=$this->deptlicenses;
		$assist=$this->assist;
		$bkdata=$this->bkdata;
		$backtoapply=$this->backtoapply;
		$this->view->user=$this->user;
		if($this->user->role =='證照審查人員'){
			
			$searchvalue = $_GET["searchvalue"];
			if($searchvalue==null){
				$searchvalue = $_POST["getsearch"];
			}
			if($searchvalue!=null){
				/////////////////////////取出搜尋同學之資料///////////////////////////
				try{
					$result =$this->msquery("SELECT a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] from [cca].[dbo].[v_assist] as a 
						LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as s ON  s.[學號]=a.stuid
						WHERE( a.u_name like '%".$searchvalue."%' OR a.ksuid like '%".$searchvalue."%' OR a.stuid like '%".$searchvalue."%' OR s.[班級名稱] like '%".$searchvalue."%')
						GROUP BY a.ksuid,a.stuid,a.u_name,s.[英文姓名],s.[身分證字號],s.[班級名稱],s.[學部名稱],s.[科系名稱],s.[電子信箱] ORDER BY a.ksuid ASC", array($this->user->department_id,$types,$years));	
					$search = $result['rows'];
					$this->view->studata=$search;
					$this->view->searchvalue = $searchvalue;
				} catch (Zend_Db_Exception $e) {
					die($e->getMessage());
				}
			}

			$getstuid = $_GET["getstuid"];
			if($getstuid==null){$getstuid = $_POST["getstuid"];}
			
			if($getstuid!=null){
				try {	
					/////////////////////////學生個人資料/////////////////////////
					$SQLCommand2 = "SELECT * from  advedusys.dbo.View_學生清冊_輕量版 as u where u.[學號]= ? or u.KSUID = ? ;";
					
					$result2 = $this->msquery($SQLCommand2,array($getstuid,$getstuid));
					$r2 = $result2['rows'];
				}catch (Zend_Db_Exception $e) {
					die($e->getMessage());
				}
				$this->stuser->name = $r2[0]['姓名'];
				$this->stuser->enname = $r2[0]['英文姓名'];
				$this->stuser->stuid = $r2[0]['學號'];
				$this->stuser->department = $r2[0]['科系名稱'];
				$this->stuser->department_id =$r2[0]['科系代碼'];
				// $this->stuser->department_id =35;
				$this->stuser->email = $r2[0]['電子信箱'];
				$this->stuser->tel = $r2[0]['行動電話'];
				$this->stuser->user_id = $r2[0]['KSUID'];
				$this->stuser->school = $r2[0]['學部名稱'];
				$this->stuser->sex = $r2[0]['性別'];
				$this->stuser->class = $r2[0]['班級名稱'];
				$this->stuser->twid = $r2[0]['身分證字號'];
				$this->view->stuser = $this->stuser;
				////////////////學生證照負責單位//////////////
				$this->unit->information=35;
				$do = $_GET["do"];
				if($do==null){
					$do = $_POST["do"];
				}
				try{
					if($do!=null){ 
						switch ($do) {
							//上傳證照圖片
							case 'addlicensesimg':
								//圖片上傳程式碼
								$upload_dir=$this->licenses_dir;
								$tcode=$_POST['tcode'];
								$getsearch=$_POST['getsearch'];
								$licensesimg = $_FILES["licensesimg"]["name"]; // 檔案名稱
								$ext = pathinfo($licensesimg, PATHINFO_EXTENSION); // 副檔名
								if($licensesimg==""||$ext==""){
								}else{
									if($ext=='png'||$ext=='jpg'||$ext=='gif'||$ext=='jpeg'){
										//建立上傳檔案的位置
										$new_pic_name = $this->stuser->stuid.'-'.$tcode.'.'.$ext;
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
								$this->_redirect('power/admin/adminbasis?searchvalue='.$getsearch.'&getstuid='.$this->stuser->stuid);
							break;
							//ajax 通報證照
							case 'insertassist':
								$mytcode =$_GET["mytcode"];//證照代號
								$myissue =$_GET["myissue"];//證照發照日
								//查詢是否存在assist	
								try {	
									$Result = $this->msquery("SELECT * FROM cca.dbo.".$assist." 
												WHERE ksuid = ? AND stuid = ? AND tcode = ?;"
												,array($this->stuser->user_id,$this->stuser->stuid,$mytcode)); //取出通報資料
									$rows = $Result['rows'];
									//$this->view->debugLog(count($rows),1);	
									
								} catch (Zend_Db_Exception $e) {
									die($e->getMessage());
								}	
								//檢查是否第一次通報
								if(count($rows) == 0){
									//是第一次通報
									try {	
										$Result = $this->msquery("insert into cca.dbo.".$assist." (years,semester,name,ksuid,stuid,tcode,issue,lesson,gender,
													month,department,program,ip)										
													select
													YEAR(DATEADD(month, -7, getdate())) -1911,
													CASE WHEN MONTH(GETDATE()) in (1,8,9,10,11,12)
													then 1 else 2 END,
													?,?,?,?,?,?,?,
														MONTH(GETDATE()),?,?,?
													from cca.dbo.edudb e where e.tcode = ?",array($this->stuser->name,$this->stuser->user_id,$this->stuser->stuid,$mytcode,$myissue,
																			$this->stuser->class,$this->stuser->sex,$this->stuser->department,$this->stuser->school,$this->user->ip,$mytcode)
																		);																
										$this->_helper->json->sendJson($Result['rows']);
									} catch (Zend_Db_Exception $e) {
										die($e->getMessage());
									}							 
								} else {
								//非第一次通報
									$SQLCommand = "update cca.dbo.".$assist." set 
													lesson = ?,
													issue = ?,
													ip = ?,
													updated = getdate()
													where stuid = ?
														and ksuid= ? 
														and  tcode = ? 
														and  status <= 2
													;";	
									//$this->view->mss = $SQLCommand; 
									try {	
										$Result = $this->msquery($SQLCommand,array($class,$myissue,$this->user->ip,$this->stuser->stuid,$this->stuser->user_id,$mytcode));
										$this->_helper->json->sendJson($Result['rows']);
									} catch (Zend_Db_Exception $e) {
										die($e->getMessage());
									}	
									$premise = '該證照重覆通報!僅補寄及變更日期';
								}		
							break;
						}
					//預設之資料
					}else{
						$admintype = $_GET["admintype"];
						if($admintype==null){$admintype='0';}
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
						$statusresult = $this->msquery($statussql,array($this->stuser->department_id,'105'));
						$status = $statusresult['rows'][0]['認列狀態'];
						if($status==1){
							$infseachdept=$this->stuser->department_id;
						}else{
							$infseachdept=$this->unit->information;
						}
						switch ($admintype) {
							case '1':
								/////////////////////////資訊證照/////////////////////////
								$SQLCommand1 = "SELECT a.assist_id,a.edudb_id,a.department,a.ksuid,a.stuid,a.u_name,a.created,a.tcode,a.cername,a.issue,a.ctype,
								a.category,a.series,a.organization,a.gender,a.equivalent,a.s_name,a.status,a.comment,a.types,back.[審核狀態] as 返回狀態  , t.證照類別項目ID,t.類別名稱,專業狀態,資訊狀態
								from [cca].[dbo].[v_assist] as a 
								LEFT JOIN [cca].[dbo].".$backtoapply." as back on a.assist_id = back.assist_id 
								LEFT JOIN [cca].[dbo].[證照E證照類別] as t on t.證照類別項目ID = a.types
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
								where a.stuid = ? and a.status BETWEEN 2 and 8 and a.types = ".$this->types_key[1]." ;";		
								$result1 =$this->msquery($SQLCommand1, array($infseachdept,$this->stuser->stuid));	
								$r1 = $result1['rows'];
								//取出證照圖片有無狀態
								$count = count($r1);
									for($i=0;$i<$count;$i++){
										$r1[$i]['圖片狀態']=0;
										if(file_exists($this->licenses_dir.'/'.$this->stuser->stuid.'-'.$r1[$i]['tcode'].'.jpg')){
											$r1[$i]['圖片狀態']=1;
										}
										
									}	
								$this->view->data = $r1;
							break;
							case '2':
								
								/////////////////////////專業證照/////////////////////////
								$SQLCommand2 = "SELECT a.assist_id,a.edudb_id,a.department,a.ksuid,a.stuid,a.u_name,a.created,a.tcode,a.cername,a.issue,a.ctype,
								a.category,a.series,a.organization,a.gender,a.equivalent,a.s_name,a.comment,a.status ,t.證照類別項目ID,t.類別名稱,專業狀態,資訊狀態
								from [cca].[dbo].[v_assist] as a 
												LEFT JOIN edudb as b on a.tcode = b.tcode
												LEFT JOIN status as c on a.status = c.status_id
												LEFT JOIN [cca].[dbo].[證照E證照類別] as t on t.證照類別項目ID = a.types
												LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態 as 專業狀態
													FROM [cca].[dbo].".$deptlicenses." as deptl 
													where deptl.時間 = ( 
														SELECT MAX(deptl2.時間) 
															FROM [cca].[dbo].".$deptlicenses." as deptl2 
															WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[2]."  and deptl2.dept_id=35 and deptl2.[學年]=105
															GROUP BY deptl2.edudb_id )) as vpl on vpl.edudb_id =a.edudb_id
												LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態  as 資訊狀態 
													FROM [cca].[dbo].".$deptlicenses." as deptl 
													where deptl.時間 = ( 
														SELECT MAX(deptl2.時間) 
															FROM [cca].[dbo].".$deptlicenses." as deptl2 
															WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別] = ".$this->types_key[1]."  and deptl2.dept_id= ? and deptl2.[學年]=105
															GROUP BY deptl2.edudb_id )) as vil on vil.edudb_id =a.edudb_id 
												where a.stuid = ? and a.status BETWEEN 2 and 8 and a.types = ".$this->types_key[2]." ;";		
								$result2 =$this->msquery($SQLCommand2, array($infseachdept,$this->stuser->stuid));	
								$r2 = $result2['rows'];
								//取出證照圖片有無狀態
								$count = count($r2);
									for($i=0;$i<$count;$i++){
										$rㄉ[$i]['圖片狀態']=0;
										if(file_exists($this->licenses_dir.'/'.$this->stuser->stuid.'-'.$r2[$i]['tcode'].'.jpg')){
											$r2[$i]['圖片狀態']=1;
										}
										
									}	
								$this->view->data = $r2;
							break;
							case '3':

							break;
							case '0':
								/////////////////////////我的證照/////////////////////////
								$SQLCommand = "SELECT *, b.name as cname,c.name as status_name
										from [cca].[dbo].[v_assist] as a
										LEFT JOIN edudb as b on a.tcode = b.tcode
										LEFT JOIN status as c on a.status = c.status_id 
										LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態 as 專業狀態
													FROM [cca].[dbo].".$deptlicenses." as deptl 
													where deptl.時間 = ( 
														SELECT MAX(deptl2.時間) 
															FROM [cca].[dbo].".$deptlicenses." as deptl2 
															WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別]=".$this->types_key[2]."  and deptl2.dept_id=35 and deptl2.[學年]=105
															GROUP BY deptl2.edudb_id )) as vpl on vpl.edudb_id =b.edudb_id
										LEFT JOIN (SELECT deptl.edudb_id,deptl.認列狀態  as 資訊狀態 
													FROM [cca].[dbo].".$deptlicenses." as deptl 
													where deptl.時間 = ( 
														SELECT MAX(deptl2.時間) 
															FROM [cca].[dbo].".$deptlicenses." as deptl2 
															WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.[類別]=deptl.[類別] and deptl2.[類別]=".$this->types_key[1]."  and deptl2.dept_id= ? and deptl2.[學年]=105
															GROUP BY deptl2.edudb_id )) as vil on vil.edudb_id =b.edudb_id 
										where a.stuid =  ?  and (status_id='1' or status_id='0')";	//單位後續需傳進來	
								$result =$this->msquery($SQLCommand, array($infseachdept, $this->stuser->stuid));	
								$r = $result['rows'];
								//取出證照圖片有無狀態
								$count = count($r);
								for($i=0;$i<$count;$i++){
									
									if(file_exists($this->licenses_dir.'/'.$this->stuser->stuid.'-'.$r[$i]['tcode'].'.jpg')){
										$r[$i]['圖片狀態']=1;
									}else{
										$r[$i]['圖片狀態']=0;
									}
								}
								$this->view->data = $r;
								break;
							}
						$this->view->licenses_url =$this->licenses_url;
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
		}
    }
	//系級是否依照電算設定
	public function deptinfcheckAction() {
		$this->view->types_value=$this->types_value;
		$this->view->types_key=$this->types_key;
		try{
			$deptinfself=$this->deptinfself;
			/////////////////////////登入者資料///////////////////////////
			$this->view->user = $this->user;
			$do = $_GET["do"];
			switch ($do) {
				case 'insertdeptinfself':
					$openstatus=$_GET['openstatus'];//證照承認(1)或不承認(0)
					$years=$_GET['years'];
					$updatetime = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
					$SQLCommand="INSERT INTO [cca].[dbo].".$deptinfself." ( dept_id , ksu_id , 認列狀態 , 時間 , ip , 學年) VALUES ( ? , ? , ? , ? ,? , ?);"; 
					$result = $this->msquery($SQLCommand,array($this->user->department_id,$this->user->user_id,$openstatus,$updatetime,$this->user->ip,$years));
					$this->_helper->json->sendJson(array('success'=>'true'));
				break;
				default:
					$yearsSQLCommand = "SELECT years.[學年] FROM [cca].[dbo].[證照E學年] as years ORDER BY years.[學年];";
					$yearsresult = $this->msquery($yearsSQLCommand,array());
					$years = $yearsresult['rows'];
					$count = count($years);
					//取得總共有多少學年，並將該學年該系的認列狀態代入
					for($i=0;$i<$count;$i++){
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
						$statusresult = $this->msquery($statussql,array($this->user->department_id,$years[$i]['學年']));
						$status = $statusresult['rows'][0]['認列狀態'];
						if($status==null){
							$years[$i]['status']=0;
						}else{
							$years[$i]['status']=$status;
						}
					}
					//將結果帶往前端
					$this->view->yearsdata = $years;
				}
		}catch(Zend_Db_Exception $e){
			echo $e->getMessage();
			// 設定 savesuccess.phtml 需要使用的訊息
			$this->view->messages = $this->_helper->getHelper('FlashMessenger')->getMessages();
		}
    }
	//系級單位設定證照認列
	public function deptAction() {
		$this->view->types_value=$this->types_value;
		$this->view->types_key=$this->types_key;
		try{
			//ajax 傳回動作
			$do = $_GET["do"];
			$deptlicenses=$this->deptlicenses;
			$deptinfself=$this->deptinfself;
			$licensesgroup=$this->licensesgroup;
			if($do!=null){ 
				switch ($do) {
					//動態取出發照機構
					case 'insertdeptlic':
						$edudb_id=$_GET['edudb_id'];//證照編號
						$openstatus=$_GET['openstatus'];//證照承認(1)或不承認(0)
						$types=$_GET['types'];//證照為資訊(inf)或專業(pro)
						$years=$_GET['years'];
						$updatetime = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						$SQLCommand="INSERT INTO [cca].[dbo].".$deptlicenses." ( edudb_id , dept_id , ksu_id , 認列狀態, 類別 , 時間 , ip , 學年) VALUES ( ? , ? , ? , ? , ? , ? , ? , ?);"; 
						$result = $this->msquery($SQLCommand,array($edudb_id,$this->user->department_id,$this->user->user_id,$openstatus,$types,$updatetime,$this->user->ip,$years));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
					//新增群組名稱
					case 'insertgroup':
						$groupvalue=$_GET['groupvalue'];//群組名稱
						$years=$_GET['years'];
						$types=$_GET['types'];
						$updatetime = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						$SQLCommand="INSERT INTO [cca].[dbo].".$licensesgroup." ( 群組名稱 , dept_id , 類別 , 學年 , 時間 , ip) VALUES ( ? , ? , ? , ? , ? , ?);"; 
						$result = $this->msquery($SQLCommand,array($groupvalue,$this->user->department_id,$types,$years,$updatetime,$this->user->ip));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
					//申請新增證照
					case 'insertapplyedudb':
						$name = $_GET["name"];
						$ctype = $_GET["ctype"];
						$series = $_GET["series"];
						$category = $_GET["category"];
						$organization = $_GET["organization"];
						$equivalent = $_GET["equivalent"];
						$information = $_GET["information"];
						$trialdate = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						
						$SQLCommand="INSERT INTO [cca].[dbo].[證照E申請增加證照] ( 證照名稱 , 國內外證照 , 級數 , 證照類別 , 證照類型 , 發照單位 , 相當於 , 申請單位 , 申請人 , 申請時間 , 申請IP) VALUES ( ? , ? , ? , ? , ? , ? ,? , ? , ? , ? ,?);"; 
						$result = $this->msquery($SQLCommand,array($name,$ctype,$series,$category,$information,$organization,$equivalent,$this->user->department_id,$this->user->user_id,$trialdate,$this->user->ip));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
					//修改群組名稱
					case 'updategroup':
						$newgroupvalue=$_GET['newgroupvalue'];//群組名稱
						$id=$_GET['id'];
						$updatetime = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						$SQLCommand1 = "UPDATE [cca].[dbo].".$licensesgroup." SET 群組名稱 =? , 時間 = ?, ip =? WHERE 群組項目ID = ? ;";
						$result =$this->msquery($SQLCommand1, array($newgroupvalue,$updatetime,$this->user->ip,$id));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
					//修改群組名稱
					case 'closegroup':
						$id=$_GET['id'];
						$updatetime = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						$SQLCommand1 = "UPDATE [cca].[dbo].".$licensesgroup." SET 時間 = ? , 狀態 = ? WHERE 群組項目ID = ? ;";
						$result =$this->msquery($SQLCommand1, array($updatetime,'0',$id));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
				}
			}else{
			//預設之資料
				$yearsSQLCommand = "SELECT years.[學年] FROM [cca].[dbo].[證照E學年] as years ORDER BY years.[學年];";
				$yearsresult = $this->msquery($yearsSQLCommand,array());
				$yearsdata = $yearsresult['rows'];
				$this->view->yearsdata = $yearsdata;
				$types=$_GET['types'];
				$years=$_GET['years'];
				if($years==null||$years==""){
					$maxyearsSQLCommand = "SELECT max(years.[學年])as maxyears FROM [cca].[dbo].[證照E學年] as years ;";
					$maxyearsresult = $this->msquery($maxyearsSQLCommand,array());
					$maxyears = $maxyearsresult['rows'][0];
					$years=$maxyears['maxyears'];
				}
				$this->view->years = $years;
				$allSQLCommand = "SELECT edu.category FROM [cca].[dbo].[edudb] as edu where edu.category!='' GROUP BY edu.category ORDER BY edu.category ;";
				$allresult =$this->msquery($allSQLCommand, array());	
				$allbookmark = $allresult['rows'];
				$this->view->bookmarkdata = $allbookmark;
				$bookmark = $_GET["bookmark"];
				if($bookmark==null){ $bookmark='recognition'; }
				switch ($bookmark) {
					case 'search':
						$searchvalue = $this->_request->getPost("searchvalue");
						if($searchvalue!=null){
							/////////////////////////取出搜尋出之證照///////////////////////////
							$SQLCommand = "SELECT edu.edudb_id,edu.tcode,edu.name,edu.ctype,edu.series,edu.category,edu.organization,edu.information,edu.open_status as edu_status,deptlic.認列狀態,deptlic.時間
											FROM [cca].[dbo].[edudb] as edu 
											left join ( 
												SELECT deptl.edudb_id,deptl.認列狀態,deptl.時間 
												FROM [cca].[dbo].".$deptlicenses." as deptl 
												where deptl.時間 = ( 
													SELECT MAX(deptl2.時間) 
													FROM [cca].[dbo].".$deptlicenses." as deptl2 
													WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.dept_id = ? and deptl2.類別 = ? and deptl2.[學年]=?
													GROUP BY deptl2.edudb_id 
												)
											) as deptlic 
											on edu.edudb_id = deptlic.edudb_id 
											where edu.tcode like '%".$searchvalue."%' or edu.name like '%".$searchvalue."%' or edu.organization like '%".$searchvalue."%';";
							$result =$this->msquery($SQLCommand, array($this->user->department_id,$types,$years));	
							$search = $result['rows'];
							$this->view->searchdata = $search;
							$this->view->searchvalue =$searchvalue;
						}	
						break;
					case 'recognition':
						/////////////////////////取出目前認列之證照///////////////////////////
						$SQLCommand = "SELECT edu.edudb_id,edu.tcode,edu.name,edu.ctype,edu.series,edu.category,edu.organization,edu.information,edu.open_status as edu_status,deptlic.認列狀態,deptlic.時間
										FROM [cca].[dbo].[edudb] as edu 
										left join ( 
											SELECT deptl.edudb_id,deptl.認列狀態,deptl.時間 
											FROM [cca].[dbo].".$deptlicenses." as deptl 
											where deptl.時間 = ( 
												SELECT MAX(deptl2.時間) 
												FROM [cca].[dbo].".$deptlicenses." as deptl2 
												WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.dept_id = ? and deptl2.類別 = ? and deptl2.學年= ? 
												GROUP BY deptl2.edudb_id 
											)
										) as deptlic 
										on edu.edudb_id = deptlic.edudb_id 
										where deptlic.認列狀態 = '1';";
						$result =$this->msquery($SQLCommand, array($this->user->department_id,$types,$years));	
						$licenses = $result['rows'];
						$this->view->licensesdata = $licenses;
						break;
					case 'group':
						/////////////////////////取出群組證照///////////////////////////
						$SQLCommand = "SELECT * FROM [cca].[dbo].[證照E群組] where dept_id=? and [類別]=? and [學年]=? and [狀態] = ? ;";
						$result =$this->msquery($SQLCommand, array($this->user->department_id,$types,$years,'1'));	
						$group = $result['rows'];
						$this->view->groupdata = $group;
						break;
					default:
						/////////////////////////取出該類別證照///////////////////////////
						$SQLCommand = "SELECT edu.edudb_id,edu.tcode,edu.name,edu.ctype,edu.series,edu.category,edu.organization,edu.information,edu.open_status as edu_status,deptlic.認列狀態,deptlic.時間
											FROM [cca].[dbo].[edudb] as edu 
											left join ( 
												SELECT deptl.edudb_id,deptl.認列狀態,deptl.時間 
												FROM [cca].[dbo].".$deptlicenses." as deptl 
												where deptl.時間 = ( 
													SELECT MAX(deptl2.時間) 
													FROM [cca].[dbo].".$deptlicenses." as deptl2 
													WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.dept_id = ? and deptl2.類別 = ? and deptl2.學年=? 
													GROUP BY deptl2.edudb_id 
												)
											) as deptlic 
											on edu.edudb_id = deptlic.edudb_id where edu.category = ?;"; 
						$result =$this->msquery($SQLCommand, array($this->user->department_id,$types,$years,$bookmark));	
						$licenses = $result['rows'];
						$this->view->licensesdata = $licenses;
				}
				/////////////////////////登入者資料///////////////////////////
				$this->view->user = $this->user;
			}
		}catch(Zend_Db_Exception $e){
			echo $e->getMessage();
			// 設定 savesuccess.phtml 需要使用的訊息
			$this->view->messages = $this->_helper->getHelper('FlashMessenger')->getMessages();
		}
    }
	//職涯管理證照
	public function adminlicensesAction() {
		$this->view->types_value=$this->types_value;
		$this->view->types_key=$this->types_key;
		try{
			$deptlicenses=$this->deptlicenses;
			//ajax 傳回動作
			$do = $_GET["do"];
			if($do!=null){ 
				switch ($do) {
					//更改證照通報顯示狀況更新
					case 'updatestatus':
						$edudb_id = $_GET["edudb_id"];
						$open_status = $_GET["open_status"];//要詢問的單位
						$SQLCommand1 = "UPDATE [cca].[dbo].[edudb]  SET open_status = ? WHERE edudb_id = ?";
						$this->msquery($SQLCommand1, array($open_status,$edudb_id));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
					//新增證照
					case 'insertedudb':
						$tcode = $_GET["tcode"];
						$scode = $_GET["scode"];
						$name = $_GET["name"];
						$ctype = $_GET["ctype"];
						$series = $_GET["series"];
						$category = $_GET["category"];
						$organization = $_GET["organization"];
						$equivalent = $_GET["equivalent"];
						$information = $_GET["information"];
						$trialdate = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						
						$SQLCommand="INSERT INTO [cca].[dbo].[edudb] ( tcode , scode , name , ctype , series , category , trialdate , information , organization , equivalent,code) VALUES ( ? , ? , ? , ? , ? , ? ,? , ? , ? , ? ,?);"; 
						$result = $this->msquery($SQLCommand,array($tcode,$scode,$name,$ctype,$series,$category,$trialdate,$information,$organization,$equivalent,'1'));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
				}
			//預設之資料
			}else{
				$allSQLCommand = "SELECT edu.category FROM [cca].[dbo].[edudb] as edu where edu.category!='' GROUP BY edu.category ORDER BY edu.category ;";
				$allresult =$this->msquery($allSQLCommand, array());	
				$allbookmark = $allresult['rows'];
				$this->view->bookmarkdata = $allbookmark;

				$bookmark = $_GET["bookmark"];
				if($bookmark==null){ $bookmark='公職考試'; }
				switch ($bookmark) {
					case 'search':
						$searchvalue = $this->_request->getPost("searchvalue");
						if($searchvalue!=null){
							/////////////////////////取出搜尋出之證照///////////////////////////
							$SQLCommand = "SELECT edu.edudb_id,edu.tcode,edu.name,edu.ctype,edu.series,edu.category,edu.organization,edu.information,edu.open_status as edu_status,deptlic.認列狀態,deptlic.時間
											FROM [cca].[dbo].[edudb] as edu 
											left join ( 
												SELECT deptl.edudb_id,deptl.認列狀態,deptl.時間 
												FROM [cca].[dbo].".$deptlicenses." as deptl 
												where deptl.時間 = ( 
													SELECT MAX(deptl2.時間) 
													FROM [cca].[dbo].".$deptlicenses." as deptl2 
													WHERE deptl2.edudb_id = deptl.edudb_id and deptl2.dept_id = ? and deptl2.類別 = ? 
													GROUP BY deptl2.edudb_id 
												)
											) as deptlic 
											on edu.edudb_id = deptlic.edudb_id 
											where edu.tcode like '%".$searchvalue."%' or edu.name like '%".$searchvalue."%' or edu.organization like '%".$searchvalue."%';";
							$result =$this->msquery($SQLCommand, array($this->user->department_id,$types));	
							$search = $result['rows'];
							$this->view->searchdata = $search;
							$this->view->searchvalue =$searchvalue;
						}	
						break;
					default:
						/////////////////////////取出該類別證照///////////////////////////
						$SQLCommand = "SELECT edu.edudb_id,edu.tcode,edu.name,edu.ctype,edu.series,edu.category,edu.organization,edu.information,edu.open_status as edu_status
											FROM [cca].[dbo].[edudb] as edu where edu.category = ?;"; 
						$result =$this->msquery($SQLCommand, array($bookmark));	
						$licenses = $result['rows'];
						$this->view->licensesdata = $licenses;
				}
			}
			/////////////////////////登入者資料///////////////////////////
			$this->view->user = $this->user;
		}catch(Zend_Db_Exception $e){
			echo $e->getMessage();
			// 設定 savesuccess.phtml 需要使用的訊息
			$this->view->messages = $this->_helper->getHelper('FlashMessenger')->getMessages();
		}
    }
	//Q&A
	public function adminmessageAction() {
		$this->view->types_value=$this->types_value;
		$this->view->types_key=$this->types_key;
		try{
				//ajax 傳回動作
			$do = $_GET["do"];
			if($do!=null){ 
				switch ($do) {
					//更新回覆內容
					case 'updateadvisory':
						$advisory=$this->advisory;
						$advisory_id = $_GET["advisory_id"];
						$cont = $_GET["cont"];
						$track = $_GET["track"];
						$answer=$this->user->user_id ;
						$answer_time = date ("Y-m-d H:i:s" , mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'))) ;
						$SQLCommand1 = "UPDATE cca.dbo.".$advisory." SET 回答內容 = ? , 追蹤狀態 = ? , 回答時間 = ? , 回答人 = ? WHERE 諮詢單項目ID = ? ;";
						
						$result =$this->msquery($SQLCommand1, array($cont,$track,$answer_time,$answer,$advisory_id));
						$this->_helper->json->sendJson(array('success'=>'true'));
					break;
				}
			//預設之資料
			}else{
				$advisory=$this->advisory;
				$bookmark = $_GET["bookmark"];
				if($bookmark==null){ $bookmark='noreply'; }
				switch ($bookmark) { 
					case 'noreply': 
						/////////////////////////取出尚未處理問題/////////////////////////
						$SQLCommand = "SELECT * FROM  cca.dbo.".$advisory."  as a LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as u
										ON a.學號 =u.[學號] where a.回答內容 is null ;";
						
						$result =$this->msquery($SQLCommand, array());	
						$r = $result['rows'];
						$this->view->data = $r;
						break;
					case 'track': 
						/////////////////////////取出需追蹤之問題/////////////////////////
						$trackSQLCommand = "SELECT * FROM  cca.dbo.".$advisory."  as a LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as u
										ON a.學號 =u.[學號] where a.追蹤狀態=1";
						$trackresult =$this->msquery($trackSQLCommand, array());	
						$trackr = $trackresult['rows'];
						$this->view->trackdata = $trackr;
						break;

					case 'all': 
						/////////////////////////取出所有問題///////////////////////////
						$allSQLCommand = "SELECT * FROM  cca.dbo.".$advisory."  as a LEFT JOIN advedusys.dbo.View_學生清冊_輕量版 as u
										ON a.學號 =u.[學號] ";
						$allresult =$this->squery($allSQLCommand, array());	
						$all = $allresult['rows'];
						$this->view->alldata = $all;
						break;
					default:	

						break;
				}
			}
			/////////////////////////登入者資料///////////////////////////
			$this->view->user = $this->user;
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

