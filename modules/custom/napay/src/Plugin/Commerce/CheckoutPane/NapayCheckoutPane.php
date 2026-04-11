<?php

namespace Drupal\napay\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node; 
use GuzzleHttp\Exception\ServerException;


/**
 * @CommerceCheckoutPane(
 *  id = "napay_checkout_pane",
 *  label = @Translation("Mobile money."),
 *  display_label = @Translation("Mobile money."),
 *  default_step = "string",
 *  wrapper_element = "string",
 * )
 */
class NapayCheckoutPane extends CheckoutPaneBase implements  CheckoutPaneInterface {
    
        protected $node_id;
        protected $total;
        protected $total_price;
        protected $order_number;
    




      /**
      * {@inheritdoc}
      */
      public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Builds the pane form.
		
		$store = \Drupal::service('commerce_store.current_store')->getStore();
		
		$cart = \Drupal::service('commerce_cart.cart_provider')->getCart('default',$store);
		
		$this->total_price = $cart->getTotalPrice();
		
		$this->total = $this->total_price->getNumber();
		
		$help_text  = "<p><img src='/sites/default/files/mobile-money.png' alt='mobile money'/></p>";
		$help_text .= "<p class='pinst'>1. Saisissez le numero de téléphone utilisé pour le transfert Mobile Money.</p>";
		$help_text .= "<p class='pinst'>2. Appuyez sur le bouton <i>Continuer vers le récapitulatif de la commande</i>.</p>";
		$help_text .= "<p class='pinst'>3. Vous allez recevoir une demande de paiement de <b>$this->total_price</b> sur votre numéro.</p>";
		
		$attr = array("placeholder" => "Saisissez votre numéro de téléphone");
		
		$current_path = \Drupal::service('path.current')->getPath();
		
		\Drupal::logger('napay')->notice("Le chemin : ".$current_path);
		$path = explode("/",$current_path );
		$this->order_number = (int)$path[2];
		
		\Drupal::logger('napay')->notice("Order number : ".$this->order_number);
		

		

        $pane_form['mobile_number'] = array(
          '#type' => 'textfield',
          '#title' => $help_text,
          '#size' => '10',
          '#attributes' => $attr,
          '#required' => TRUE,
          );
        return $pane_form;
      }
      /**
      * {@inheritdoc}
      */
      public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Validates the pane form.
		
	    	$values = $form_state->getValue($pane_form['#parents']);
	    	$prix = explode(" ",$this->total_price);
            $talo = (int) $prix[0];
            $client = \Drupal::httpClient();
            


		
			if (!preg_match("#^06{1}[0-9]{7}$#", $values['mobile_number']))
			{
			    \Drupal::logger('napay')->notice("Regex verification");
				$form_state->setError($pane_form,t("<p class='bg-danger text-white p-3 rounded-pill'>Entrez un numéro Mtn valide</p>"));
			}
			
			
			else {
			     try {
     $request = $client->post('http://para.sportground.net/pay', [
    'form_params' => [
      'numero' => $values['mobile_number'],
       'montant' => $prix[0],

    ]
  ]);
 

   $statusCode = $request->getStatusCode();
   \Drupal::logger('napay')->notice("Code Status ".$statusCode);
 $response = $request->getBody();
  }
  
      catch (ServerException $e) {
           \Drupal::logger('napay')->notice("Server Exception: " . $e->getMessage());
    echo "Server Exception: " . $e->getMessage();
  }
 
  
    catch (RequestException $e) {
    watchdog_exception('Napay', $e->getMessage());
  }
 
   
		  
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
