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

	$data = array('pick_time'=>new \Datetime());
	$data = array_merge($data, $request->query->all());
$now=new \Datetime();
	/** @var Symfony\Component\Form\Form $form */
	$form = $app['form.factory']->createNamedBuilder(null, 'form', $data, array('csrf_protection' => false))
		->setAction('/')
		->add('name', 'text', array(
			'label' => '您(微信名)',
			'required' => true
		))
		->add('tel', 'text', array(
			'label' => '电话',
			'required' => true
		))
	//todo:range
		->add('quality', 'text', array(
			'label' => '来几套',
			'required' => true
		))

		->add('pick_time', 'datetime', array(
			'data' => $now->modify('next Sunday 10:00:00'),
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
				'`quality`' => $data['quality'],
				'`remark`' => $data['remark'],
				'`order_time`' => $now,
			), array(
				PDO::PARAM_STR,
				PDO::PARAM_STR,
				PDO::PARAM_STR,
				'datetime'
			));
			$app['db']->commit();
			$message = " 恭喜恭喜，您已经在".$now->format("Y-m-d H:i")."成功订上了".$data['quality']."套煎饼果子";
		} catch (\Exception $e) {
			$app['db']->rollback();
			$message = " Error: ". $e->getMessage();
		}

		return $app['twig']->render('index.twig', array(
			'form' => $form->createView(),
			'message' => $message
		));
	}

	// display the form
	return $app['twig']->render('index.twig', array(
		'form' => $form->createView(),
		'message'=> null
	));
});

$app->run();
