<?php

/**
 * Created by PhpStorm.
 * User: sarthak
 * Date: 28/5/18
 * Time: 5:07 PM
 */

namespace Cocorico\CoreBundle\Form\Handler\Frontend;

use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Entity\ListingLocation;
use Cocorico\CoreBundle\Model\Manager\ListingImportManager;
use Cocorico\UserBundle\Entity\User;
use Cocorico\UserBundle\Form\Handler\RegistrationFormHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle Listing Import
 *
 */
class ListingImportHandler
{
    protected $request;
    protected $listingImportManager;
    protected $registrationHandler;

    /**
     * @param RequestStack $requestStack
     * @param EntityManager $em
     * @param ListingImportManager $listingImportManager
     * @param RegistrationFormHandler $registrationHandler
     */
    public function __construct(RequestStack $requestStack, ListingImportManager $listingImportManager, RegistrationFormHandler $registrationHandler) {
        $this->request = $requestStack->getCurrentRequest();
        $this->listingImportManager = $listingImportManager;
        $this->registrationHandler = $registrationHandler;
    }

    /**
     * @return Listing
     */
    public function init()
    {
        $listing = new Listing();
        return $listing;
    }

    /**
     * @author Sarthak Patidar
     *
     * Process Listing
     *
     * @param Listing $listing
     * @param User $user
     * @param ListingLocation $listingLocation
     * @param array $eventTypes
     * @param array $subcategory
     * @param bool $userSet
     *
     * @return Booking|string
     */
    public function processImport(Listing $listing, User $user, ListingLocation $listingLocation, $eventTypes, $subcategory, $userSet)
    {
        return $this->importListing($listing, $user, $listingLocation, $eventTypes, $subcategory, $userSet);
    }

    /**
     * @author Sarthak Patidar
     *
     * @param Listing $listing
     * @param User $user
     * @param ListingLocation $listingLocation
     * @param array $eventTypes
     * @param array $subcategory
     *
     * @return string|boolean
     *
     */
    private function importListing(Listing $listing, User $user, ListingLocation $listingLocation, $eventTypes, $subcategory, $userSet)
    {
        if(!$userSet){
            $this->registrationHandler->handleRegistration($user);
        }
        if($user->getId()){
            $listing->setUser($user);
            $listing->setLocation($listingLocation);
            $this->listingImportManager->save($listing);
            $id = $listing->getId();
            if($id != NULL){
                $this->listingImportManager->saveListingEventType($eventTypes,$listing);
                $this->listingImportManager->saveListingSubCategory($subcategory,$listing);
                return true;
            }
        }
        return false;
    }

    /**
     * @author Sarthak Patidar
     *
     * Add Category to Listing Import
     *
     * @param Listing $listing
     * @param array $categories
     *
     * @return Listing;
     */
    public function addImportCategory(Listing $listing, $categories){

        $listingCategories = $categories["listingListingCategories"];
        $listingCategoriesValues = isset($categories["categoriesFieldsSearchableValuesOrderedByGroup"]) ? $categories["categoriesFieldsSearchableValuesOrderedByGroup"] : array();

        if ($categories) {
            $listing = $this->listingImportManager->addCategories(
                $listing,
                $listingCategories,
                $listingCategoriesValues
            );
        }

        return $listing;
    }
}
