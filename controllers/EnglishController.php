<?php session_start();
class Power_EnglishController extends Zend_Controller_Action
{
	

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

		$request = $this->getRequest();
		$myksuid = $this->getRequest()->getPost('myksuid', null);
		$mystuid = $this->getRequest()->getPost('mystuid', null);
		$mytcode = $this->getRequest()->getPost('mytcode', null);
		$myissue = $this->getRequest()->getPost('myissue', null);
		$mycreated = $this->getRequest()->getPost('mycreated', null);
		$myinformation = $this->getRequest()->getPost('myinformation', null);
		$myprogram = $this->getRequest()->getPost('myprogram', null);
		$mycername = $this->getRequest()->getPost('mycername', null);
		$mycomment = $this->getRequest()->getPost('mycomment', null);
		$myreason = $this->getRequest()->getPost('myreason', null);
		$mylesson = $this->getRequest()->getPost('mylesson', null);
		$mydepartment = $this->getRequest()->getPost('mydepartment', null);
		$myusdepartment = $this->getRequest()->getPost('myusdepartment', null);
		$mycategory = $this->getRequest()->getPost('mycategory', null);
		$myurl = 'power/english/index';
		$ip = '120.114.51.51';
		$userid = 'S101000078';
		$this->view->originator = $this->getRequest()->getPost('originator', null);

		switch ($_POST['action']) {
			case '上傳成績': //application/vnd.ms-excel
				$exttable = array('application/vnd.ms-excel'=>'.xls',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'.xlsx');
				$ext = $exttable[$_FILES["loadExcel"]["type"]];
				$excelpath = realpath(APPLICATION_PATH . '/../public/upload/english/excel/'); 
				$this->view->excelfile  = $_FILES['loadExcel']['name'];
				$filetempname = $_FILES['loadExcel']['tmp_name'];
				//上傳後的文件名地址
				$uploadfile = $excelpath.str_replace("/", "-", $myissue,$count) .'-'. $mycategory . $ext;
				
				if (file_exists($uploadfile)) {
					$this->view->message = 'Excel檔案已存在!';
					$_GET['bookmark']="uploadexcel";
					return true;
				} else {
				
				if(!move_uploaded_file($filetempname,$uploadfile)) {
					//如果上傳文件成功，就執行導入操作
					$this->view->message =  $filetempname .
					"檔案從暫存區移動至$uploadfile 時發生錯誤!";
					return false;
				} elseif ($_FILES["file"]["size"] > 20000) {
					$this->view->message =  "檔案太大!";
					return false;
				}

				}

				switch ($mycategory) {
					case 'BULATS':
						$start = 4;
						$gain = array('E','C','D','I','J');  //一定要以pid,name,ename,degree,level次序
						$score = array('F','G','H');
						$photo = 'K';
						$xtitle = '1414476991b1cc9c032f1df75d3862f6a8e92b2b';
					break;
					case 'NETPAW':
						$start = 1;
						$gain = array('B','A','A','H','D');  //一定要以pid,name,ename,degree,level次序
						$score = array('F','G');
						$photo = 'S';
						$xtitle = 'b581e2f2fb4118ea674ae063a6a38445fd2ae9f8';
					break;
					case 'GET':
						$start = 1;
						$gain = array('K','I','J','U','D');  //一定要以pid,name,ename,degree,level次序
						$score = array('R','S','T');
						$photo = 'V';
						$xtitle = 'e94c36779965bb462e57b3ced7d2c2053d67eb4c';
					break;
					case 'TOEIC':
						$start = 5;
						$gain = array('E','C','D','M','N');  //一定要以pid,name,ename,degree,level次序
						$score = array('J','K','L');
						$photo = 'O';
						$xtitle = 'ea3e335e65d430a2d4e095dbb3054cd1fa08484d';
					break;
					case 'CSEPT':
						$start = 1;
						$gain = array('D','C','C','H','G');  //一定要以pid,name,ename,degree,level次序
						$score = array('E','F','G');
						$photo = 'I';
						$xtitle = 'cb6c5fdec993e657c9f817e04c5fb0500185e08e';
					break;
					default:
					break;
				}

				$cols = array(
					'category'=>$mycategory,
					'start'=>$start,
					'issue'=>$myissue,
					'gain'=>$gain, //一定要以pid,name,ename,degree,level次序
					'score'=>$score);

				$result = $this->view->importEnglishExcel($uploadfile,$cols,$xtitle,$ext,$photo);

				if($result['status']){
					$this->view->message = '成績暫存匯入成功!';

				} else {
					$this->view->message = $result['message'] . '成績暫存匯入失敗!';
				}
			break;

			case '上傳images':
				// step 1.
				$this->writeImageFiles();
				// step 2.
				$this->MultiUpdate($this->msdb,$_SESSION['sql_commands']);
				//$this->_redirect('power/english/index?bookmark=finish');
			break;

			default:
				break;
		}

		//$this->_redirect('power/english/uploadimage');
		//session_destroy();
    }
	public function studentAction(){
		
		try {	
			$result = $this->view->msquery( $this->msdb,
											"SELECT v.班級名稱, v.KSUID, v.學號, v.姓名, g.pid, g.created, g.issue, g.tcode, s.name, s.score, g.[level]
											FROM [cca].[dbo].[gain] g
											JOIN [cca].[dbo].[subject] s ON s.pid = g.pid
											JOIN [advedusys].[dbo].[View_學生清冊] v ON v.身分證字號 = g.pid",
											array());
			$r = $result['rows'];
			$this->view->englishdata=$r;

		} catch (Zend_Db_Exception $e) {
			$this->view->message = $e->getMessage();
		}	
	}
	//test
	public function testAction(){

	}

	//上傳多張照片&改名
	function writeImageFiles() {
		// 判斷 $_FILES是否存在?
		if(isset($_FILES['files'])){
			$errors= array();

			foreach($_FILES['files']['tmp_name'] as $key => $tmp_name ){
				
				$file_name 	=	$_FILES['files']['name'][$key];		// 檔案名稱
				$file_size 	=	$_FILES['files']['size'][$key];		// 檔案大小
				$file_tmp 	=	$_FILES['files']['tmp_name'][$key]; // 檔案暫時的名字
				$file_type 	=	$_FILES['files']['type'][$key]; 	// 檔案類型
				
				// 判斷檔案大小2MB
				if($file_size > 2097152){
					$errors[]='檔案大小要小於2MB哦!';
				}  

				// 檔案路徑
				$path = realpath(APPLICATION_PATH . '/../public/upload/english/temp/'); 
				if(empty($errors)==true){

					$pid   = $_SESSION['stds'][$file_name]['pid'];
					$tcode = $_SESSION['stds'][$file_name]['tcode'];

					try {	

						$result = $this->view->msquery( $this->msdb,
														"SELECT KSUID, [學號] as stuid 
															FROM [advedusys].[dbo].[View_學生清冊] 
															WHERE [身分證字號] = ? ; ",
														array( $pid ));
						$r = $result['rows'];
						
						$img_name = $r[0]['KSUID'].'-'.$r[0]['stuid'].'-'.$tcode.'.jpg';

						//把圖片上傳後，移到temp  ***move_uploaded_file(檔名,搬移位置)
						move_uploaded_file($file_tmp, $path.'\\'.$img_name);    

						
					} catch (Zend_Db_Exception $e) {
						$this->view->message = $e->getMessage();
					}	
					$img .= "<div class='col-md-4'><img src='/ccawww/public/upload/english/temp/".$img_name."' class='img-thumbnail'><p>".$pid."</p></div>";

				}else{
					$this->view->message = print_r($errors);
				}
			}
			if(empty($error)){
				$this->view->message ="<strong>恭喜恭喜~~~</strong> 掃描檔上傳成功!!!";
				$this->view->image = $img;			
			}
		}
	}
	//多行update
	function MultiUpdate($msdb, $sql_commands) {
		$result = array();
		//$this->db->beginTransaction();
		try {
			foreach($sql_commands as $sql_command) {
				$result['code'] = $this->view->msquery($msdb, $sql_command, array());
			}
			$result['text'] = $sql_commands;
			//$this->db->commit();
		} catch (Exception $e) {
			//$this->db->rollback();
			return array('status'=>false,'message'=>$e->getMessage());
		}
		return array('status'=>true,'message'=>'');
	}
}



