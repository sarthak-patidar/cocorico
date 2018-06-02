<?php

/**
 * Created by PhpStorm.
 * User: sarthak
 * Date: 28/5/18
 * Time: 5:07 PM
 */

namespace Cocorico\CoreBundle\Controller\Frontend;

use Cocorico\CoreBundle\Document\ListingAvailability;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Cocorico\UserBundle\Entity\User;
use Cocorico\UserBundle\Form\Handler\RegistrationFormHandler;
use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Entity\ListingLocation;
use Cocorico\CoreBundle\Entity\ListingListingCategory;
use Cocorico\CoreBundle\Entity\ListingSubCategory;
use Cocorico\CoreBundle\Entity\ListingEventType;
use Cocorico\CoreBundle\Entity\ListingListingEventType;
use Cocorico\CoreBundle\Entity\ListingListingSubCategory;

ob_start();
/**
 * Listing controller.
 *
 * @Route("/listing")
 */

class ListingImportController extends Controller
{

    //todo: Develop routine to add event types and subcategories of a listing
    /**
     * @author Sarthak Patidar
     *
     * Creates a Listing import entity.
     *
     * @Route("/import", name="cocorico_listing_import")
     * @Method({"GET"})
     *
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function import(Request $request){
        $rowNo = array();
        $collection = array();
        $rowNo[0] = 0;
        $i = 0;
        $count = 1;

        // $fp is file pointer to file listing.csv
        if (($fp = fopen("listing_all.csv", "r")) !== FALSE) {
            while (($row = fgetcsv($fp, 1000, ",")) !== FALSE || $count <501) {
                //Create new listing and user entity
                $userSet = false;
                $listingLocation = new ListingLocation();
                $listingImportHandler = $this->get('cocorico.import.handler.listing');
                $listing = $listingImportHandler->init();
                //populate properties of a listing Location Entity
                $listingLocation->setCountry('IN');
                $listingLocation->setCity($row[6]);

                $checkUserExists = $this->getDoctrine()->getRepository('CocoricoUserBundle:User')->findOneBy(array('email' => $row[3]));
                if($checkUserExists) {
                    $userSet = true;
                    $user = $checkUserExists;
                } else{
                    $user = new User();

                    //populate default properties of user entity
                    $user->setNationality('IN');
                    $user->setCountryOfResidence('IN');
                    $user->setPhonePrefix('+91');
                    if($row[7] != 'NULL'){
                        $user->setGender($row[7]);
                    }

                    //populate variable properties of user entity
                    $user->setFirstName($row[1]);
                    $email = $row[3];
                    $user->setPhone($row[2]);
                    $user->setUsername($email);
                    $user->setEmail($email);
                    $user->setLastName($row[0]);
                    $user->setPlainPassword('starclinch');
                }

                // populating listing entity
                $categoryName = $row[9];
                $category = array();
                if($categoryName == 'LIVE BAND'){
                    $categoryId = 30;
                } elseif ($categoryName == 'DJ'){
                    $categoryId = 31;
                } elseif ($categoryName == 'DANCER/TROUPE' || $categoryName == 8){
                    $categoryId = 29;
                } elseif ($categoryName == 'ANCHOR/EMCEE'){
                    $categoryId = 32;
                } elseif ($categoryName == 'SINGER'){
                    $categoryId = 33;
                } elseif ($categoryName == 'CELEBRITY APPEARANCE'){
                    $categoryId = 34;
                } elseif ($categoryName == 'MAKE-UP ARTIST/STYLIST'){
                    $categoryId = 35;
                } elseif ($categoryName == 'MODEL'){
                    $categoryId = 36;
                } elseif ($categoryName == 'COMEDIAN'){
                    $categoryId = 37;
                } elseif ($categoryName == 'INSTRUMENTALIST'){
                    $categoryId = 38;
                } elseif ($categoryName == 'MAGICIAN'){
                    $categoryId = 39;
                } elseif ($categoryName == 'VARIETY ARTIST'){
                    $categoryId = 40;
                } elseif ($categoryName == 'PHOTO/VIDEOGRAPHER'){
                    $categoryId = 42;
                } elseif ($categoryName == 'SPEAKER'){
                    $categoryId = 41;
                } else {
                    print_r("Invalid Category at Row: ".$count);
                    break;
                }

                $listingCategories = array();
                array_push($listingCategories,$categoryId);
                $category['listingListingCategories'] = $listingCategories;
                $listing->setLocation($listingLocation);
                $listing->setPrice($row[5]."-".$row[4]);
                $listing->setTitle($row[12]);
                $listing->SetDescription("Usually Performs in following Events: ".$row[11]);
                $listing = $listingImportHandler->addImportCategory($listing, $category);

                // import event types & subcategories for artist
                $eventTypes = explode(',', $row[11]);
                $subCategory = explode(',', $row[10]);

                //Import Function
                $success = $listingImportHandler->processImport($listing, $user, $listingLocation, $eventTypes, $subCategory, $userSet);
                $num = count($row);
                $rowNo[$i] = $num;
                $i++;
                array_push($collection,$row);
                $status = "Insert ";
                if(!$success){
                    $status .= "Break at ".$row[0];
                    break;
                }else{
                    $status .= "Push";
                }
                if($count == 140){
                    break;
                }
                $count++;
            }
            fclose($fp);
        }
        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:import.html.twig',
            array(
                'count' => $count,
                'status' => $status,
            )
        );
    }

}