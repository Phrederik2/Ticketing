<?php

namespace OCA\Ticketing\Controller;
use OCP\AppFramework\PublicShareController;
//use OCP\AppFramework\Http\PublicTemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;

use OCP\IRequest;
use OCP\ISession;



class PublicAPIController extends PublicShareController {

    public function __construct($AppName, IRequest $request,ISession $session){

       
		parent::__construct($AppName, $request, $session);
		
	
	}
        /**
         * Return the hash of the password for this share.
         * This function is of course only called when isPasswordProtected is true
         */
        protected function getPasswordHash(): string {
                return md5('secretpassword');
        }

        /**
        * Validate the token of this share. If the token is invalid this controller
        * will return a 404.
        */
        public function isValidToken(): bool {
            return $this->getToken() === 'PublicView';
        }

        /**
         * Allows you to specify if this share is password protected
         */
        protected function isPasswordProtected(): bool {
                return false;
        }

        /**
         * Your normal controller function. The following annotation will allow guests
         * to open the page as well
         * 
		 * @NoCSRFRequired
         * @PublicPage
         */
        public function get()  {
            
			return new PublicTemplateResponse('ticketing', 'index'); // Work your magic
        }

        /**
         * Your normal controller function. The following annotation will allow guests
         * to open the page as well
         * 
		 * @NoCSRFRequired
         * @PublicPage
         */
        public function getPOST()  {
            
			return $this->get();
        }
}