<?php
 /*
 *@file 
 * Contains \Drupal\napay\Controller\NotifyController
 */

  namespace Drupal\napay\Controller;
  use Drupal\Core\Controller\ControllerBase;
  use Symfony\Component\HttpFoundation\Request;
  use Psr\Log\LoggerInterface;
  use Symfony\Component\DependencyInjection\ContainerInterface;
  use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
  use Symfony\Component\HttpFoundation\Response;
  use Drupal\node\Entity\Node; 
  use Symfony\Component\HttpFoundation\BinaryFileResponse;
  use Ramsey\Uuid\Uuid;
  use GuzzleHttp\Exception\ServerException;
  
  class NotifyController extends ControllerBase implements ContainerInjectionInterface {
    protected $subKey;
    protected $xReference;
    protected $ReferenceToPayID;
	  
   /**
   * The http request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
	  
  protected $request;
	  
   /**
   * The http response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
	  
  protected $response;  
	  
  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  
  protected $logger;
  
  public function generateUid()
{
   return Uuid::uuid4();
}
   
  
  /**
   * Constructs a new NotifyController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */

   
  public function __construct(LoggerInterface $logger) {
  
    $this->logger = $logger;
    $this->subKey = "f8cdb161cd004355ad028d6e5ec84b48";
    $this->xReference = "4f58899c-fe51-4c39-b98d-e27ab93a73ca";
    
    //$this->subKey = "9d5e32f537d8413d9c861600aab5331b";
    //$this->xReference = "f8729882-143d-41e0-9281-8ed76e05105f";

    }  

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.default')
    );
    }
    
  public function Callbackapi(){
        
        // 1. Cree un utilisateur

$client = \Drupal::httpClient();

  
  $body = "
  {
    'providerCallbackHost': 'https://hub-distribution.com/wc-api/mtn_mobile_money'
  }
  ";

  $request = $client->post('https://sandbox.momodeveloper.mtn.com/v1_0/apiuser', [
    'headers' => [
      'X-Reference-Id' => $this->xReference,
      'Ocp-Apim-Subscription-Key' => $this->subKey,
  ],
    'body' => $body,
  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  //\Drupal::state()->set('my_data', 'calling');
  //$data = \Drupal::state()->get('my_data');


  $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}
public function getUser(){
    // Cette fonction permet de verifier l'utilisateur créé
    $client = \Drupal::httpClient();
      $request = $client->get("https://sandbox.momodeveloper.mtn.com/v1_0/apiuser/$this->xReference",  [
    'headers' => [
      'X-Reference-Id' => $this->xReference,
      'Ocp-Apim-Subscription-Key' => $this->subKey,
  ],
  ]);
  
    $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  //\Drupal::state()->set('my_data', 'calling');
  //$data = \Drupal::state()->get('my_data');


  $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 
}
public function apiKey(){
    
     // 2. Cree un api pour l'utilisateur


  $client = \Drupal::httpClient();
 

  $request = $client->post("https://sandbox.momodeveloper.mtn.com/v1_0/apiuser/$this->xReference/apikey", [
    'headers' => [
      'Ocp-Apim-Subscription-Key' => $this->subKey,
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

   $api = json_decode($response);
   $api->{'apiKey'};
   $this->logger->info("Momo Api Key ".$api->{'apiKey'});

  \Drupal::state()->set('momoapi', $api->{'apiKey'});
  //$data = \Drupal::state()->get('my_data');


  $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function motoken(){


  $client = \Drupal::httpClient();
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("$this->xReference:" . $pass);
 

  $request = $client->post('https://sandbox.momodeveloper.mtn.com/collection/token/', [
    'headers' => [
      'Authorization' => "Basic $auth",
      'Ocp-Apim-Subscription-Key' => $this->subKey,
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

   $api = json_decode($response);

   //"access_token": "string",
   //"token_type": "string",
   //"expires_in": 0
 

  \Drupal::state()->set('accesstoken', $api->{'access_token'});

  $temps = time() + $api->{'expires_in'};
  \Drupal::state()->set('expires', $temps);

  $this->logger->info("Le token ".$api->{'access_token'});
  $this->logger->info("Le token expires ".$temps);
  $this->logger->info("apiKey".$pass);

  //$data = \Drupal::state()->get('my_data');


 $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}  



public function requestToPay(){
    
    	$requete = Request::createFromGlobals();
    	
         $numero = "067101515";

		$montant = "15.0";
    	
    	
    	
	if ($requete->isMethod('POST')) {
	    $numero = $requete->request->get('numero');

		$montant = $requete->request->get('montant');
		
		$this->logger->info("Le numero ".$numero. "  Le momtant ".$montant);


	}
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("$this->xReference:" . $pass);
  $uuid = Uuid::uuid4();
  $uuid = (string) $uuid;
  $this->ReferenceToPayID = $uuid ;
  $this->logger->info("Reference ID X ".$uuid. "-".$this->ReferenceToPayID);
  $this->logger->info("Uuid ".$uuid);
  \Drupal::state()->set('payRef', $uuid);
 

  $request = $client->post('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay', [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => $this->subKey,
      'X-Reference-Id' => $uuid,
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  'body' => json_encode([
    "amount" => $montant,
    "currency" => "EUR",
    "externalId" => "516127822",
    "payer" => [
      "partyIdType" => "MSISDN",
      "partyId" => $numero
    ],
    "payerMessage" => "Veuillez confirmer le paiement",
    "payeeNote" => "Merci"
    ])

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  $temps = \Drupal::state()->get('expires');


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);


  $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response, "redId" => $this->xReference ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}


public function requestToPayStatus(){
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("$this->xReference:" . $pass);
  $this->logger->info("Request To Pay ID ".$this->ReferenceToPayID);
    $ref = \Drupal::state()->get('payRef');
 
 try {

  $request = $client->get("https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay/$ref", [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => $this->subKey,
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $decoded_data = json_decode($request->getBody(), true);
  $response = $decoded_data['status'];



  $temps = \Drupal::state()->get('expires');

  


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);


  $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 
}

catch (ServerException $e) {
    echo "Server Exception: " . $e->getMessage();
}

}

public function accountBalance(){
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("$this->xReference:" . $pass);
 
 

  $request = $client->get('https://sandbox.momodeveloper.mtn.com/collection/v1_0/account/balance', [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => $this->subKey,
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  $temps = \Drupal::state()->get('expires');


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);
  $this->logger->info("Le body ".$response );



   $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function accountActive(){
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("$this->xReference:" . $pass);
 
 

  $request = $client->get('https://sandbox.momodeveloper.mtn.com/collection/v1_0/accountholder/msisdn/064781414/active', [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => $this->subKey,
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  $temps = \Drupal::state()->get('expires');


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);
  $this->logger->info("Le body ".$response );



   $retour = new Response(json_encode( ["Code Status" => $statusCode, "corps" => $response ] )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}
	  
    
	
  /**
   *  process() Cette fonction reçois la requête envoyé par le mobile gateway
   *  pour créer l'utilisateur ou encore la commande
   */
   
  public function process(){
	$request = Request::createFromGlobals();
	if ($request->isMethod('POST'))
	{
		$this->logger->info('dans le bloc nouvelle formile ');
		$numero = $request->request->get('numero');
		$body = $request->request->get('body');
		$montant = $request->request->get('montant');

		
		// Create node object with attached file.
		$node = Node::create([
		  'type'        => 'notification',
		  'title'       => $montant." de ".$numero,
		  'body'        => $body,
		  'field_amount' => $montant,
		  'field_telephone_number' => $numero,
		  'field_user_id' => $userid,

		]);
		$node->save();
		
		      $response = new Response(json_encode(['success' => 1])) ;
			// $this->createOrder($user->id(),$montant);
			$response->headers->set('Content-Type', 'application/json');
			return $response; 
		
		

	}
        else {
            
 
	    
	    
            $this->logger->info('test rest');
           		$response = new Response(json_encode(['success' => 3])) ;
			// $this->createOrder($user->id(),$montant);
			$response->headers->set('Content-Type', 'application/json');
			return $response; 
        }
	}





}
 
