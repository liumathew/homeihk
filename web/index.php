<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

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

	$data = array();
	$data = array_merge($data, $request->query->all());

	/** @var Symfony\Component\Form\Form $form */
	$form = $app['form.factory']->createNamedBuilder(null, 'form', $data, array('csrf_protection' => false))
		->setAction('/')
		->add('name', 'text', array(
			'label' => '您',
			'required' => true
		))
		->add('quality', 'text', array(
			'label' => '来几套',
			'required' => true
		))
		->add('spicy', 'checkbox', array('label' => '要辣子的', 'required' => true))
		->add('onion', 'checkbox', array('label' => '要放葱的', 'required' => true))
		->add('remark', 'text', array(
			'required' => false
		))
		->add('submit', 'submit', array('label' => '订上'))
		->getForm();

	$form->handleRequest($request);

	if ($form->isValid()) {
		$data = $form->getData();

		$app['db']->beginTransaction();

		try {
			//insert into results
			$app['db']->insert('jianbingguozi', array(
				'`name`' => $data['name'],
				'`quality`' => $data['quality'],
				'`spicy`' => $data['spicy'],
				'`onion`' => $data['onion'],
				'`remark`' => $data['remark'],
			), array(
				PDO::PARAM_STR,
				PDO::PARAM_STR,
				PDO::PARAM_STR,
				PDO::PARAM_INT,
				PDO::PARAM_STR
			));
			$message = " 成功订购";
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
