<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

date_default_timezone_set('America/Montreal');
$app['debug'] = true;

//$app->register(new Binfo\Silex\MobileDetectServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__ . '/views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
	'db.options' => array(
		'host' => 'localhost',
		'port' => '3306',
		'dbname' => 'homeihk',
		'user' => 'jianbingguozi',
		'password' => 'jianbingguozi123!',
		'charset' => 'UTF8'
	),
));

$app->register(new Silex\Provider\FormServiceProvider());

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
	'translator.messages' => array(),
));

$app->match('/', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {

	$rows = null;
	$message = null;
	$reserve = null;

	$data = array();
	$data = array_merge($data, $request->query->all());

	$datetime = new \Datetime();

	/** @var Symfony\Component\Form\Form $form */
	$form = $app['form.factory']->createNamedBuilder(null, 'form', $data, array('csrf_protection' => false))
		->setAction('/')
		->add('name', 'text', array(
			'label' => '您的称呼',
			'required' => true
		))
		->add('tel', 'text', array(
			'label' => '电话',
			'required' => true
		))
	//todo:range
		->add('quality', 'number', array(
			'label' => '来几套',
			'required' => true
		))

		->add('pick_time', 'datetime', array(
			'data' => $datetime->modify('next Sunday 10:00:00'),
			'hours'=> array(9,10,11,12,1,2,3,4,5),
			'minutes'=> array(00,15,30,45),
			'format' => 'yyyy-MM-dd',
			'label' => '取煎饼时间',
			'required' => true
		))

		->add('remark', 'textarea', array(
			'label' => '备注',
			'required' => false
		))
		->add('submit', 'submit', array('label' => '订上'))
		->getForm();

	$form->handleRequest($request);

	if ($form->isValid()) {
		$data = $form->getData();

		$app['db']->beginTransaction();

		$now =  new \Datetime();
		try {
			//insert into results
			$app['db']->insert('jianbingguozi', array(
				'`name`' => $data['name'],
				'`tel`'=> $data['tel'],
				'`quality`' => $data['quality'],
				'`remark`' => $data['remark'],
				'`pick_time`' => $data['pick_time'],
				'`order_time`' => $now,
			), array(
				PDO::PARAM_STR,
				PDO::PARAM_STR,
				PDO::PARAM_STR,
				PDO::PARAM_STR,
				'datetime',
				'datetime'
			));
			$app['db']->commit();
			$message = " 恭喜恭喜，您已经在".$now->format("Y-m-d H:i")."成功订上了".$data['quality']."套煎饼果子";
		} catch (\Exception $e) {
			$app['db']->rollback();
			$message = " Error: ". $e->getMessage();
		}

	}

	if(array_key_exists('name',$data) && !empty($data['name'])){
		try{

			$sqlCount = 'SELECT Sum(quality) FROM jianbingguozi WHERE order_time> "'.date("Y-m-d H:i:s", strtotime('last Sunday')).'"';
			/** @var \Doctrine\DBAL\Driver\Statement $stmt */
			$stmtCount = $app['db']->prepare($sqlCount);
			$stmtCount->bindValue(':name', $data['name']);
			$stmtCount->execute();
			$reserve = $stmtCount->fetchColumn();

			$sql = 'SELECT * FROM jianbingguozi WHERE name=:name AND order_time> "'.date("Y-m-d H:i:s", strtotime('last Sunday')).'"';
			/** @var \Doctrine\DBAL\Driver\Statement $stmt */
			$stmt = $app['db']->prepare($sql);
			$stmt->bindValue(':name', $data['name']);
			$stmt->execute();
			$rows = $stmt->fetchAll();
		} catch (\Exception $e) {
			$message = " Error: ". $e->getMessage();
		}
	}

	// display the form
	return $app['twig']->render('index.twig', array(
		'rows'=> $rows,
		'reserve'=> $reserve,
		'form' => $form->createView(),
		'message'=> $message
	));
});

$app->match('/delete/{id}/{name}', function ($id, $name) use ($app) {
	try{
		$app['db']->delete('jianbingguozi', array(
			'id' => $id,
		));
	}catch (\Exception $e){
		return $app->redirect('/?name='.$name.'&message=出错了');
	}

	return $app->redirect('/?name='.$name);

});

$app->run();
