<?php

/*
 * This file is part of the Cocorico package.
 *
 * (c) Cocolabs SAS <contact@cocolabs.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cocorico\CoreBundle\Form\Handler\Frontend;

use Cocorico\CoreBundle\Document\ListingAvailability;
use Cocorico\CoreBundle\Entity\Booking;
use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Entity\ListingLocation;
use Cocorico\CoreBundle\Model\Manager\ListingManager;
use Cocorico\UserBundle\Entity\User;
use Cocorico\UserBundle\Form\Handler\RegistrationFormHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle Listing Form
 *
 */
class ListingFormHandler
{
    protected $request;
    protected $listingManager;
    protected $registrationHandler;


    /**
     * @param RequestStack            $requestStack
     * @param ListingManager          $listingManager
     * @param RegistrationFormHandler $registrationHandler
     */
    public function __construct(RequestStack $requestStack, ListingManager $listingManager, RegistrationFormHandler $registrationHandler) {
        $this->request = $requestStack->getCurrentRequest();
        $this->listingManager = $listingManager;
        $this->registrationHandler = $registrationHandler;
    }


    /**
     * @return Listing
     */
    public function init()
    {
        $listing = new Listing();
        $listing = $this->addImages($listing);
        $listing = $this->addCategories($listing);
        return $listing;
    }

    /**
     * Process form
     *
     * @param Form $form
     *
     * @return Booking|string
     */
    public function process($form)
    {
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $this->request->isMethod('POST') && $form->isValid()) {
            return $this->onSuccess($form);
        }

        return false;
    }

    /**
     * @param Form $form
     * @return bool
     */
    private function onSuccess(Form $form)
    {
        /** @var Listing $listing */
        $listing = $form->getData();
//        var_dump($listing->getLocation());
//        Login is done in BookingNewType form

        if ($this->request->request->get('_username') || $this->request->request->get('_password')) {
        }

        elseif ($form->has('user') && $form->get('user')->has("email")) {
            $user = $listing->getUser();
            $this->registrationHandler->handleRegistration($user);
        }

        $this->listingManager->save($listing);
        return true;
    }

    /**
     * @author Sarthak Patidar
     *
     * Process Listing
     *
     * @param Listing $listing
     * @param User $user
     * @param ListingLocation $listingLocation
     *
     * @return Booking|string
     */
    public function processImport(Listing $listing, User $user, ListingLocation $listingLocation)
    {
            return $this->importListing($listing, $user, $listingLocation);
    }

    /**
     * @author Sarthak Patidar
     *
     * @param Listing $listing
     * @param User $user
     * @param ListingLocation $listingLocation
     *
     * @return string|boolean
     *
     */
    private function importListing(Listing $listing, User $user, ListingLocation $listingLocation)
    {
        $this->registrationHandler->handleRegistration($user);

        if($user->getId()){
            $listing->setUser($user);
            $listing->setLocation($listingLocation);
            $this->listingManager->save($listing);
            return true;
        }
       return false;
    }

    /**
     * @param  Listing $listing
     * @return Listing
     */
    private function addImages(Listing $listing)
    {
        //Files to upload
        $imagesUploaded = $this->request->request->get("listing");
        $imagesUploaded = $imagesUploaded["image"]["uploaded"];

        if ($imagesUploaded) {
            $imagesUploadedArray = explode(",", trim($imagesUploaded, ","));
            $listing = $this->listingManager->addImages(
                $listing,
                $imagesUploadedArray
            );
        }

        return $listing;
    }

    /**
     * Add selected categories and corresponding fields values from post parameters while listing deposit
     *
     * @param  Listing $listing
     * @return Listing
     */
    public function addCategories(Listing $listing)
    {
        $categories = $this->request->request->get("listing_categories");

        $listingCategories = isset($categories["listingListingCategories"]) ? $categories["listingListingCategories"] : array();
        $listingCategoriesValues = isset($categories["categoriesFieldsSearchableValuesOrderedByGroup"]) ? $categories["categoriesFieldsSearchableValuesOrderedByGroup"] : array();

        if ($categories) {
            $listing = $this->listingManager->addCategories(
                $listing,
                $listingCategories,
                $listingCategoriesValues
            );
        }

        return $listing;
    }
}
