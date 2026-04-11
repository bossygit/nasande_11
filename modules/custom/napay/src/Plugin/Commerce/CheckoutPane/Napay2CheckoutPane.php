<?php

namespace Drupal\napay\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node; 
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Exception\ServerException;


/**
 * @CommerceCheckoutPane(
 *  id = "napay2_checkout_pane",
 *  label = @Translation("Payment Status."),
 *  display_label = @Translation("Payment Status."),
 *  default_step = "string",
 *  wrapper_element = "string",
 * )
 */
class Napay2CheckoutPane extends CheckoutPaneBase implements  CheckoutPaneInterface {
    
        protected $node_id;
        protected $total;
        protected $total_price;
        protected $order_number;
        // https://www.drupal.org/project/smart_ip/issues/3318820




      /**
      * {@inheritdoc}
      */
      public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Builds the pane form.
		

		
		$help_text = "<p>Une fois que vous avez validé la demande de paiement sur Momo, veuillez cliquer sur le bouton <i>Payer et terminer
        votre achat.</i></p>";

		

        $pane_form['mobile_status'] = array(
          '#type' => 'textfield',
          '#title' => $help_text,
          '#size' => '10',
          '#required' => FALSE,
          '#attributes' => array('style'=>'display: none;'),
          );
        return $pane_form;
      }
      
      
      
      
      /**
      * {@inheritdoc}
      */
      public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Validates the pane form.
        
		
	    	$values = $form_state->getValue($pane_form['#parents']);
	    	
	    	try {
	    	  $client = \Drupal::httpClient();
	    	  $request = $client->get("http://para.sportground.net/status");
  
  $statusCode = $request->getStatusCode();
  \Drupal::logger('napay')->notice("Code Status ".$statusCode);
  $response = $request->getBody();
  $decoded_data = json_decode($request->getBody(), true);
  
  if ((int)$statusCode == 200 AND $decoded_data['corps'] == "SUCCESSFUL") {
      \Drupal::logger('napay')->notice("Le paiement a reussi, merci");
      return TRUE;
  }
  
  else {
      \Drupal::logger('napay')->notice("Le paiement a échoué");
      $form_state->setError($pane_form,t("<p class='bg-danger text-white p-3 rounded-pill'>Le paiement n'a pas abouti</p>"));
  }


    }
      catch (ServerException $e) {
          \Drupal::logger('napay')->notice("Server Exception: " . $e->getMessage());
    echo "Server Exception: " . $e->getMessage();
}
		

      }
      
      
      
      
      
      /**
      * {@inheritdoc}
      */
      public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Handles the submission of an pane form.
        $values = $form_state->getValue($pane_form['#parents']);

        /*
        $order = \Drupal\commerce_order\Entity\Order::load($this->order_number);
        $payments = \Drupal\commerce_order\Payment\Payment::loadByProperties(['order_id' => $order->id()]);
        
        foreach ($payments as $payment) {
        $payment->setState('completed')->save();
                }
        */

        \Drupal::logger('napay')->notice("SubmitForm Complete order todo");
      }
      


      

      

      
      


}
