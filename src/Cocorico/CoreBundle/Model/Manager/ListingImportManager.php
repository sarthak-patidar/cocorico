<?php
/**
 * Created by PhpStorm.
 * User: sarthak
 * Date: 28/5/18
 * Time: 5:51 PM
 */

namespace Cocorico\CoreBundle\Model\Manager;

use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Entity\ListingCategory;
use Cocorico\CoreBundle\Entity\ListingEventType;
use Cocorico\CoreBundle\Entity\ListingImage;
use Cocorico\CoreBundle\Entity\ListingListingCategory;
use Cocorico\CoreBundle\Entity\ListingListingCharacteristic;
use Cocorico\CoreBundle\Entity\ListingListingSubCategory;
use Cocorico\CoreBundle\Entity\ListingTranslation;
use Cocorico\CoreBundle\Mailer\TwigSwiftMailer;
use Cocorico\CoreBundle\Model\ListingCategoryFieldValueInterface;
use Cocorico\CoreBundle\Model\ListingCategoryListingCategoryFieldInterface;
use Cocorico\CoreBundle\Model\ListingOptionInterface;
use Cocorico\CoreBundle\Repository\ListingCharacteristicRepository;
use Cocorico\CoreBundle\Repository\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Cocorico\CoreBundle\Entity\ListingListingEventType;
use Cocorico\CoreBundle\Entity\ListingSubCategory;

class ListingImportManager extends BaseManager
{
    protected $em;
    protected $securityTokenStorage;
    protected $newListingIsPublished;
    public $maxPerPage;
    protected $mailer;

    /**
     * @param EntityManager   $em
     * @param TokenStorage    $securityTokenStorage
     * @param int             $newListingIsPublished
     * @param int             $maxPerPage
     * @param TwigSwiftMailer $mailer
     */
    public function __construct(
        EntityManager $em,
        TokenStorage $securityTokenStorage,
        $newListingIsPublished,
        $maxPerPage,
        TwigSwiftMailer $mailer
    ) {
        $this->em = $em;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->newListingIsPublished = $newListingIsPublished;
        $this->maxPerPage = $maxPerPage;
        $this->mailer = $mailer;
    }

    /**
     * @param  Listing $listing
     * @return Listing
     */
    public function save(Listing $listing)
    {
        $listingPublished = false;
        if (!$listing->getId()) {
            if ($this->newListingIsPublished) {
                $listing->setStatus(Listing::STATUS_PUBLISHED);
                $listingPublished = true;
            } else {
                $listing->setStatus(Listing::STATUS_TO_VALIDATE);
            }
        } else {
            //todo: replace this tracking change by doctrine event listener. (See copost UserEntityListener)
            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            $changeSet = $uow->getEntityChangeSet($listing);
            if (array_key_exists('status', $changeSet) && $listing->getStatus() == Listing::STATUS_PUBLISHED) {
                $listingPublished = true;
            }
        }
        $listing->mergeNewTranslations();
        $this->persistAndFlush($listing);

        /** @var ListingTranslation $translation */
        foreach ($listing->getTranslations() as $translation) {
            $translation->generateSlug();
            $this->em->persist($translation);
        }

        /** @var ListingOptionInterface $option */
        if ($listing->getOptions()) {
            foreach ($listing->getOptions() as $option) {
                $option->mergeNewTranslations();
                $this->persistAndFlush($option);
            }
        }

        $this->em->flush();
        $this->em->refresh($listing);

        if ($listingPublished) {
            $this->mailer->sendListingActivatedMessageToOfferer($listing);
        }

        return $listing;
    }

    /**
     * In case of new characteristics are created, we need to associate them to listing
     *
     * @param Listing $listing
     *
     * @return Listing
     */
    public function refreshListingListingCharacteristics(Listing $listing)
    {
        /** @var ListingCharacteristicRepository $listingCharacteristicRepository */
        $listingCharacteristicRepository = $this->em->getRepository('CocoricoCoreBundle:ListingCharacteristic');

        //Get all characteristics
        $listingCharacteristics = new ArrayCollection(
            $listingCharacteristicRepository->findAllTranslated($listing->getCurrentLocale())
        );

        //Remove characteristics already associated to listing
        $listingListingCharacteristics = $listing->getListingListingCharacteristics();
        foreach ($listingListingCharacteristics as $listingListingCharacteristic) {
            $listingCharacteristics->removeElement($listingListingCharacteristic->getListingCharacteristic());
        }

        //Associate new characteristics not already associated to listing
        foreach ($listingCharacteristics as $listingCharacteristic) {
            $listingListingCharacteristic = new ListingListingCharacteristic();
            $listingListingCharacteristic->setListing($listing);
            $listingListingCharacteristic->setListingCharacteristic($listingCharacteristic);
            $listingListingCharacteristic->setListingCharacteristicValue();
            $listing->addListingListingCharacteristic($listingListingCharacteristic);
        }

        return $listing;
    }

    /**
     * @param  Listing $listing
     * @param  array   $images
     * @param bool     $persist
     * @return Listing
     * @throws AccessDeniedException
     */
    public function addImages(Listing $listing, array $images, $persist = false)
    {
        //@todo : see why user is anonymous and not authenticated
        if (true || $listing && $listing->getUser() == $this->securityTokenStorage->getToken()->getUser()) {
            //Start new positions value
            $nbImages = $listing->getImages()->count();

            foreach ($images as $i => $image) {
                $listingImage = new ListingImage();
                $listingImage->setListing($listing);
                $listingImage->setName($image);
                $listingImage->setPosition($nbImages + $i + 1);
                $listing->addImage($listingImage);
            }

            if ($persist) {
                $this->em->persist($listing);
                $this->em->flush();
                $this->em->refresh($listing);
            }

        } else {
            throw new AccessDeniedException();
        }

        return $listing;
    }

    /**
     * Create categories and field values while listing deposit.
     *
     * @param  Listing $listing
     * @param  array   $categories Id(s) of ListingCategory(s) selected
     * @param  array   $values     Value(s) of ListingCategoryFieldValue(s) of the ListingCategory(s) selected
     *
     * @return Listing
     */
    public function addCategories(Listing $listing, array $categories, array $values)
    {
        foreach ($categories as $i => $category) {
            //Find the ListingCategory entity selected
            /** @var ListingCategory $listingCategory */
            $listingCategory = $this->em->getRepository('CocoricoCoreBundle:ListingCategory')->findOneById($category);

            //Create the corresponding ListingListingCategory
            $listingListingCategory = new ListingListingCategory();
            $listingListingCategory->setListing($listing);
            $listingListingCategory->setCategory($listingCategory);

            //Create the corresponding ListingCategoryFieldValue(s)
            /** @var ListingCategoryListingCategoryFieldInterface $field */
            if ($listingCategory->getFields()) {
                foreach ($listingCategory->getFields() as $field) {
                    /** @var ListingCategoryFieldValueInterface $fieldValue */
                    $fieldValue = new \Cocorico\ListingCategoryFieldBundle\Entity\ListingCategoryFieldValue();
                    $fieldValue->setListingListingCategory($listingListingCategory);
                    $fieldValue->setListingCategoryListingCategoryField($field);
                    //Set the values. Index of $values corresponds to the ListingCategoryListingCategoryField Id.
                    //It permits to associate the field value with the corresponding field.
                    //Mainly use to remove block fields when the corresponding category is unselected
                    //See Listing->getCategoriesFieldsValuesOrderedByGroup method
                    $value = isset($values[$field->getId()]["value"]) ? $values[$field->getId()]["value"] : null;
                    $fieldValue->setValue($value);

                    $listingListingCategory->addValue($fieldValue);
                }
            }


            $listing->addListingListingCategory($listingListingCategory);
        }

        return $listing;
    }


    /**
     * @author Sarthak Patidar
     *
     * Save Event types of artists
     *
     * @param array $eventTypes
     * @param Listing $listing
     *
     * @return boolean
     */
    public function saveListingEventType($eventTypes,$listing){
        foreach ($eventTypes as $event){
            $eventType = $this->em->getRepository('CocoricoCoreBundle:ListingEventType')->findOneBy(array('name' => $event));
            $listingListingEventType = new ListingListingEventType();
            $listingListingEventType->setListingId($listing);
            if(!$eventType){
                $newEventType = new ListingEventType();
                $newEventType->setName($event);
                $this->em->persist($newEventType);
                $this->em->flush();
                $listingListingEventType->setEventId($newEventType);
            }else{
                $listingListingEventType->setEventId($eventType);
            }
            $this->em->persist($listingListingEventType);
            $this->em->flush();
        }
        return true;
    }

    /**
     * @author Sarthak Patidar
     *
     * Save Event types of artists
     *
     * @param array $subcategory
     * @param Listing $listing
     *
     * @return boolean
     */
    public function saveListingSubCategory($subcategory,$listing){
        foreach ($subcategory as $name){
            $subCategory = $this->em->getRepository('CocoricoCoreBundle:ListingSubCategory')->findOneBy(array('name' => $name));
            $category = new ListingListingSubCategory();
            $category->setListingId($listing);
            if(!$subCategory){
                $newSubCategory = new ListingSubCategory();
                $newSubCategory->setName($name);
                $this->em->persist($newSubCategory);
                $this->em->flush();
                $category->setSubcategoryId($newSubCategory);
            }else{
                $category->setSubcategoryId($subCategory);
            }
            $this->em->persist($category);
            $this->em->flush();
        }
        return true;
    }
}
