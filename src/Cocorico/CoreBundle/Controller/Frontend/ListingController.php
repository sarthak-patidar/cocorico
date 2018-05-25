<?php

/*
 * This file is part of the Cocorico package.
 *
 * (c) Cocolabs SAS <contact@cocolabs.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

ob_start();
/**
 * Listing controller.
 *
 * @Route("/listing")
 */

class ListingController extends Controller
{
    /**
     * Creates a new Listing entity.
     *
     * @Route("/new", name="cocorico_listing_new")
     * @Method({"GET", "POST"})
     *
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $listingHandler = $this->get('cocorico.form.handler.listing');
        $listing = $listingHandler->init();
        $form = $this->createCreateForm($listing);
        $success = $listingHandler->process($form);

        if ($success) {
            $url = $this->generateUrl(
                'cocorico_dashboard_listing_edit_presentation',
                array('id' => $listing->getId())
            );

            $this->container->get('session')->getFlashBag()->add(
                'success',
                $this->container->get('translator')->trans('listing.new.success', array(), 'cocorico_listing')
            );
            return $this->redirect($url);
        }

        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:new.html.twig',
            array(
                'listing_data' => $success,
                'listing' => $listing,
                'form' => $form->createView(),
                )
        );
    }

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
        $request->setMethod('POST');
        $rowNo = array();
        $collection = array();
        $inserted_id = array();
        $rowNo[0] = 0;
        $i = 0;

        // $fp is file pointer to file listing.csv
        if (($fp = fopen("listing.csv", "r")) !== FALSE) {
            while (($row = fgetcsv($fp, 1000, ",")) !== FALSE) {

                //Create new listing and user entity
                $listingHandler = $this->get('cocorico.form.handler.listing');
                $listing = $listingHandler->init();
                $user = new User();
                $listingLocation = new ListingLocation();

                //populate properties of a listing Location Entity
                $listingLocation->setCountry('IN');
                $listingLocation->setCity($row[15]);
                $location = $listingLocation->getId();

                //populate default properties of user entity
                $user->setNationality('IN');
                $user->setCountryOfResidence('IN');
                $user->setPhonePrefix('+91');

                //populate variable properties of user entity
                $user->setFirstName($row[2]);
                $email = str_shuffle("sarthak")."@manual.com";
                $user->setUsername($email);
                $user->setEmail($email);
                $user->setLastName($row[0]);
                $user->setPlainPassword('sarthak');

                //populate listing entity
                $listing->setLocation($listingLocation);
                $category = array();
                array_push($category,$row[2]);
                $request->request->set('listing_categories',$category);
                $listing->setPrice(2010);
                $listing->setTitle("Artist X");
                $listing->SetDescription("Lorem Ipsum Dolor Sit Amet");

                //delete when program is live
                var_dump($location);
                print_r('<br><br>');

                //Import Function
                $success = $listingHandler->processImport($listing, $user, $listingLocation);
                $num = count($row);
                $rowNo[$i] = $num;
                $i++;
                array_push($collection,$row);
                $status = "Insert ";
                if(!$success)
                {
                    $status .= "Break";
                    break;
                }
                else
                {
                    $status .= "Push";
                    array_push($inserted_id,$success);
                }
            }
            fclose($fp);
        }

        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:import.html.twig',
            array(
                'collection' => $collection,
                'insert_id' => $inserted_id,
                'status' => $status,
            )
        );
    }

    /**
     * Creates a form to create a Listing entity.
     *
     * @param Listing $listing The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Listing $listing)
    {
        $form = $this->get('form.factory')->createNamed(
            'listing',
            'listing_new',
            $listing,
            array(
                'method' => 'POST',
                'action' => $this->generateUrl('cocorico_listing_new'),
            )
        );
        return $form;
    }

    /**
     * Finds and displays a Listing entity.
     *
     * @Route("/{slug}/show", name="cocorico_listing_show", requirements={
     *      "slug" = "[a-z0-9-]+$"
     * })
     * @Method("GET")
     * @Security("is_granted('view', listing)")
     * @ParamConverter("listing", class="Cocorico\CoreBundle\Entity\Listing", options={"repository_method" = "findOneBySlug"})
     *
     * @param Request $request
     * @param Listing $listing
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request, Listing $listing)
    {
        $reviews = $this->container->get('cocorico.review.manager')->getListingReviews($listing);

        //Breadcrumbs
        $breadcrumbs = $this->get('cocorico.breadcrumbs_manager');
        $breadcrumbs->addListingShowItems($request, $listing);

        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:show.html.twig',
            array(
                'listing' => $listing,
                'reviews' => $reviews
            )
        );
    }

}
