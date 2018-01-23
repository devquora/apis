<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class ScrapController extends Controller
{

    
  private function scrapIndiaMart($url=null){


  }

  public function index(Request $request){
  		
  		return view('scrap.index');
  		

  }

  private function otherAddresses($crawler){

    $nodeValues =$crawler->filter('#plus .clr6_P')->each(function ($node){
                       
            $address=trim($node->html());

            $temp=explode('<br>',$address); 
            $addresses=[];     
            if(count($temp)>1){
               $addresses['type']=$temp[0];
               if($temp[2])
               $addresses['address']=$temp[1].' , '.$temp[2];
             else{
               $addresses['address']=$temp[1];
             }
            }
            if($addresses)
            return   $addresses;
           
     });
    $addresses=[];
    foreach($nodeValues as $key=>$value){
      if($value)
     $addresses[]=$value;

    }

    return $addresses;
  }

  private function getContactInfo($url){
    $contactInfo=[];
   $crawler = \Goutte::request('GET', $url."enquiry.html");
  if($this->get_domain($url)!="www.indiamart.com" &&  $this->get_domain($url)!="indiamart.com"){
   $numbers =  $crawler->filter('.p27_p .fl_p.w12_p .ncnct_p.f13_p')->each(function ($node){
                       
            $contact=trim($node->html());

            return $contact;         
           
    });
     unset($numbers[0]);
     $phone_numbers=[];
     foreach($numbers as $key => $value){
        $temp=explode('<br>',$value);
        foreach($temp as $key => $value){
          if($value)
           $phone_numbers[]=$value;
        }
     }
   
     $contactInfo['phone_nos']=$phone_numbers;
     $contactInfo['addresses']= $this->otherAddresses($crawler);
   }else{

        $nodeValues =$crawler->filter('#plus .clr6_P')->each(function ($node){
                       
            $address=trim($node->html());

            $temp=explode('<br>',$address); 
            $addresses=[];     
            if(count($temp)>1){
               $addresses['type']=$temp[0];
               if(array_key_exists("2",$temp)){
               $addresses['address']=$temp[1].' , '.$temp[2];
             }else{
               $addresses['address']=$temp[1];
              }
            
            }else{

                 $addresses['phone']=$temp[0];
            }
                     
           return $addresses;
     });
    $addressesNew=[];
    $phone_numbers=[];
    foreach($nodeValues as $key=>$value){
      if(array_key_exists("phone",$value)){

       $phone_numbers[]=$value['phone'];
       unset($value['phone']);
     }else{
       $addressesNew[]=$value;
     }
   
    }
    if(empty($phone_numbers)){
    	if($crawler->filter('#mobilecontent')->count())
          $phone_numbers[]=	$crawler->filter('#mobilecontent')->text();
       
       if($crawler->filter('#telephonecontent')->count())
          $phone_numbers[]=	$crawler->filter('#telephonecontent')->text();

    	

    }
     $contactInfo['phone_nos']=$phone_numbers;
     $contactInfo['addresses']=$addressesNew; 
   }
     
     return $contactInfo;
  }
  private function getProfile($url){
       $client = new \Goutte\Client();
       $crawler = $client->request('GET', $url."profile.html");
       $status=$client->getResponse()->getStatus();
       if($status!=200){
            
            $crawler = $client->request('GET', $url);


       }
      if($this->get_domain($url)!="www.indiamart.com" &&  $this->get_domain($url)!="indiamart.com"){
             $supplier['registered_office']="";
             $supplier['title'] = "";

            if($crawler->filter('.cont1.bx2 .ds3.vr1.w22 img')->count())
             $supplier['logo'] = $crawler->filter('.cont1.bx2 .ds3.vr1.w22 img')->attr('src');
             if($crawler->filter('.clr1')->count())
              $supplier['title'] =  $crawler->filter('.clr1')->text();
            else if($crawler->filter('.cn.c2.f3.k.p18.m40.w123 a')->count())
              $supplier['title'] =  $crawler->filter('.cn.c2.f3.k.p18.m40.w123 a')->text();

             if($crawler->filter('.clr2')->count()){
                 $supplier['location'] =  $crawler->filter('.clr2')->text();
              }else if($crawler->filter('.dspc.vrty .cn.c2.f8.p18.m40')->count()){
               $supplier['location'] =  $crawler->filter('.dspc.vrty .cn.c2.f8.p18.m40')->text();
            }
             if($crawler->filter('#abtdesc')->count())
               $supplier['about'] =  $crawler->filter('#abtdesc')->text();
             else if($crawler->filter('.prn.f7.lh2.p19.tz.c7.wb.u1 p')->count())
               $supplier['about'] =  $crawler->filter('.prn.f7.lh2.p19.tz.c7.wb.u1 p')->text();
            
            if($crawler->filter('.cont3 .cont4 .fnt5')->count()){
              $supplier['registered_office'] =  $crawler->filter('.cont3 .cont4 .fnt5')->text();
            }
            else if($crawler->filter('#contact-stop .lh2.f2.f7')->count()){
                $supplier['registered_office'] =  $crawler->filter('#contact-stop .lh2.f2.f7')->text();

            }
            if($supplier['registered_office'])
            $supplier['registered_office']= preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $supplier['registered_office']));

            if($supplier['title'])
            $supplier['title']= preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $supplier['title']));
        }else{


        $supplier=  $this->indiaMartProfile($crawler,$status);


        }
       $contactInfo= $this->getContactInfo($url);
       $supplier['contact_info']=$contactInfo;
       return $supplier;
  } 

  public function indiaMartProfile($crawler,$status){
             $supplier['registered_office']="";
             $supplier['title'] = "";

            if($crawler->filter('h1')->count())
              $supplier['title'] =  $crawler->filter('h1')->text();
            elseif($crawler->filter('.fnt1.bo2')->count())
              $supplier['title'] =  $crawler->filter('.fnt1.bo2')->text();
        	
             if($crawler->filter('.fnt3.bo2.clr2.m3')->count()){
                 $supplier['location'] =  $crawler->filter('.fnt3.bo2.clr2.m3')->text();
              }else if($crawler->filter('.dspc.vrty .cn.c2.f8.p18.m40')->count()){
               $supplier['location'] =  $crawler->filter('.dspc.vrty .cn.c2.f8.p18.m40')->text();
            }

            if($crawler->filter('.fnt8_sh.ta.lnh_mn.prd1_mn')->count())
               $supplier['about'] =  $crawler->filter('.fnt8_sh.ta.lnh_mn.prd1_mn')->text();
             else if($status!=200){
              if($crawler->filter('.text-shdw.ps2.le1.clr1')->count())
               $supplier['about'] =  $crawler->filter('.text-shdw.ps2.le1.clr1')->text();
      		 }
           
            if($crawler->filter('.contact-nam')->count()){
              $supplier['registered_office'] =  $crawler->filter('.contact-nam')->text();
            }else if($crawler->filter('#contact-stop .lh2.f2.f7')->count()){
                $supplier['registered_office'] =  $crawler->filter('#contact-stop .lh2.f2.f7')->text();

            }
            if($supplier['registered_office'])
            $supplier['registered_office']= preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $supplier['registered_office']));

            if($supplier['title'])
            $supplier['title']= preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $supplier['title']));

          return $supplier;

  }

 private function createSupplier($supplier_profile){


 }

 private function getCompanies(){
       $client = new \Goutte\Client();
       $companies=[];
       for($i=1;$i<2000;$i++){
       	   $url="https://www.indiamart.com/company/".$i;
	       $crawler = $client->request('GET',$url);
	       $status=$client->getResponse()->getStatus();
	       if($status==200){
	            
	           $companies[]=$url;


	       }
       }
        dd($companies);

 }
 private function getIndiaMartProfile($url){

  $status=500;
  while($status!=200){

 	$user_agent=$this->userAgents();
    $proxy=$this->ipAddress();
    $client = new \Goutte\Client();
    $client->setHeader('User-Agent',$user_agent);
   
    $client->setClient(new \GuzzleHttp\Client(['proxy' =>$proxy ]));
    $crawler = $client->request('GET',$url);

    $status= $client->getResponse()->getStatus();
  
   }


	if($crawler->filterXpath('//input[@name="gluser_id"]')->count()){
	 	$supplier_id= $crawler->filterXpath('//input[@name="gluser_id"]')->attr('value');
	    $supplier_thumb= $crawler->filterXpath('//meta[@name="twitter:image"]')->attr('content');
	}
   // $supplier_id=18793937;
	$supplier_data=[];
	while(empty($supplier_data)){
 	$ch = curl_init();

     curl_setopt($ch, CURLOPT_URL,"https://www.indiamart.com/cgi/encode_decode_mobile.php");
     curl_setopt($ch, CURLOPT_POST, 1);
     curl_setopt($ch, CURLOPT_POSTFIELDS,
            "glusrid=".$supplier_id."&modid=MDC");
     curl_setopt($ch,CURLOPT_USERAGENT,$this->userAgents());

   	 curl_setopt($ch, CURLOPT_PROXY, $this->ipAddress());

	 // receive server response ...
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);
	$supplier_data=json_decode($server_output, true);
 }
//	print_r($supplier_data);
    $supplier_data['supplier_thumb']=$supplier_thumb;
  

	curl_close ($ch);
	return   $supplier_data;

	
}
public function ipCheck(){

 dd($_SERVER);

}
  public function scrapIndex(Request $request){
  	$url=$request->url;

  	return view('scrap.types',$url);
  }

  public function scrapSupplier(Request $request){


  //$url="http://www.srikamakshitraders.net/";
  // $url="http://www.amdoverseas.com/";
   
  // $url="http://www.amdoverseas.com/";
  // $url="http://www.articawindows.in/";
  // $url="http://www.rsassociatess.com/";
  // $url="https://www.indiamart.com/srimehta-glass-plywood/";
  // $url="https://www.indiamart.com/aadithyaupvcwindows/";
   	$url='https://www.indiamart.com/fenestabuildingsystem/';
    $completeProfile=[];
    $supplier_profile= $this->getIndiaMartProfile($url);
    sleep(1);
    
    $categories=$this->crawlSiteMap($url);
    $completeProfile['supplier']  = $supplier_profile;
    $completeProfile['categories']= $categories;
    $products=[];
    $i=0;
    

//    dd($completeProfile);
	foreach($categories as $cate=>$url){
	  if($i>0){
	   $product=  $this->scrapProducts($url['url'],$cate);
		   $products[]=$product;
		   sleep(mt_rand(1, 3));
	   
	  }
	 
	  $i++;
	}
    $completeProfile['products']=$products;

    echo $data= json_encode($completeProfile);
    file_put_contents("fenestabuildingsystem.json", $data);
   
    die();
    
  }
  private function checkProxy($proxy){
  	  $splited = explode(':',$proxy); // Separate IP and port
	  if($con = @fsockopen($splited[0], $splited[1], $eroare, $eroare_str, 3)) 
	  {
	   return true;

	  }else{

	  	return false;

	  }

  }

 
  private function ipAddress(){

  	$ipAddress=[
			"173.192.21.89:8080",
			"144.217.88.135:8080",
			"119.81.71.27:80",
			"119.81.71.27:8123",
			"37.59.47.13:3128",
			"88.157.149.250:8080",
			"185.82.212.95:8080",
			"162.243.140.150:80",
			"45.55.27.246:80",
			"185.82.212.95:8080",
			"162.243.140.150:8080",
			"45.55.27.246:8080",
			"139.59.109.146:8080",
			"139.59.109.146:3128",
			"212.237.23.60:2000",
			"173.192.128.238:25",
			"173.192.128.238:8123",
			"46.101.75.192:8118",
			"162.243.140.150:8000",
			"31.3.242.140:3128",
			"112.121.55.7:8085",
			"37.59.62.38:8080",
			"103.85.24.48:6666",
			"173.192.128.238:9999",
			"191.252.186.58:3128",
			"190.66.3.90:8081",
  	];
  	
 
  	$valid_proxy=false;

	while(!$valid_proxy){
	 $valid_ip=	$ipAddress[array_rand($ipAddress)];
	 $valid_proxy=  $this->checkProxy($valid_ip);
	}
    return $valid_ip;
  }
  private function userAgents(){

  	$agents =[
		'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',//working
		'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',//working
		'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0'//working
		
	];

	return $agents[array_rand($agents)];
 

  }

  private function scrapProducts($url,$cate){
    
    $status=500;
    $count=0;
    while($count==0){
	    try{
		 	$user_agent=$this->userAgents();
		    $proxy=$this->ipAddress();
		    $client = new \Goutte\Client();
		    $client->setHeader('User-Agent',$user_agent);
		   
		    $client->setClient(new \GuzzleHttp\Client(['proxy' =>$proxy ]));
		    $crawler = $client->request('GET',$url);

		    $status= $client->getResponse()->getStatus();
		}catch(\Exception $e){
			//print_r($e);
		}
		if($status==200){
			$count=$crawler->filter('.container.p16.bg17.fnt9')->count();
			if(!$count){
				$count=$crawler->filter('.w6_sh.m1.ds.m4_mn')->count();
			}
			
		}else{
			$count=0;
		}
        
   }




    $nodeValues =$crawler->filter('.container.p16.bg17.fnt9')->each(function ($node) use ($cate){

    if($node->filter("h2")->count())
     $product['name']= $node->filter("h2")->text();
   
     if($product['name']){
      $product['category']=$cate;
     }
    if($node->filter(".bdr15.bg1.ps2.ds3.vr2 .max-width")->count())
     $product['image']= $node->filter(".bdr15.bg1.ps2.ds3.vr2 noscript img")->attr('src');
    if($node->filter('.w16.ds3.vr2.bx2 .he10.m4')->count())
    { 
      $product['description']= $node->filter('.w16.ds3.vr2.bx2 .he10.m4')->html();
      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
    }
    if($node->filter('.w16.ds3.vr2.bx2 .fnt21.m37.ds.xt7.w27')->count()){
      $product['price']= $node->filter('.w16.ds3.vr2.bx2 .fnt21.m37.ds.xt7.w27')->html();
      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
   }

    return $product;


    });
    
    if(empty($nodeValues)){

    $nodeValues =$crawler->filter('.w6_sh.m1.ds.m4_mn')->each(function ($node) use ($cate){

    if($node->filter("h2")->count())
     $product['name']= $node->filter("h2")->text();
    
     if($product['name']){
      $product['category']=$cate;
     }
    if($node->filter(".cp img")->count())
     $product['image']= $node->filter(".cp img")->attr('src');
  
    if($node->filter('.ta.lnh_mn.fnt8_sh.bo1.m57_des')->count())
    { 

      $product['description']= $node->filter('.ta.lnh_mn.fnt8_sh.bo1.m57_des')->html();
      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
    }
    if($node->filter('.fnt18_mn.bo2.m14_new.ps2.txl .ds1')->count()){
      $product['price']= $node->filter('.fnt18_mn.bo2.m14_new.ps2.txl .ds1')->html();
      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
   }


    return $product;


    });
    }

    return $nodeValues;

    
  }
  public function crawlSiteMap($url){
    $status=500;
    while($status!=200){

	 	$user_agent=$this->userAgents();
	    $proxy=$this->ipAddress();
	    $client = new \Goutte\Client();
	    $client->setHeader('User-Agent',$user_agent);
	   
	    $client->setClient(new \GuzzleHttp\Client(['proxy' =>$proxy ]));
	    $crawler = $client->request('GET',$url."sitenavigation.html");

	    $status= $client->getResponse()->getStatus();
  
   }




     if($this->get_domain($url)!="www.indiamart.com" &&  $this->get_domain($url)!="indiamart.com"){

       
          $nodeValues =$crawler->filter('.bx2.w1.stmp.bg1.bx3.w19.m1.p18 ul ul ul li a')->each(function ($node){
               return   $products=  $node->attr('href');
  
           });

            $categories_urls=[];
           
            foreach($nodeValues as $key => $value){

                $temp= explode("#", $value);
                $category=explode(".", $temp[0]);
                $category=$category[0];
               
                $categories_urls[$category]['url']=$url. $temp[0];
              
              

            }
        return  $categories_urls;

     }else{

     	   $nodeValues =$crawler->filter('.ta.lnh_mn.fnt24_mn.bo1.fl.prd1_mn1.stmp.m30 li a')->each(function ($node){
               return   $products=  $node->attr('href');
  
           });

            $categories_urls=[];
           
            foreach($nodeValues as $key => $value){

                
                $temp    = explode("#", $value);
                $category= explode(".", $temp[0]);
                $category=$category[0];
               
                $categories_urls[$category]['url']=$url. $temp[0];
              
              

            }

            unset($categories_urls['https://www']);
            unset($categories_urls['http://www']);
            unset($categories_urls['http://']);
            unset($categories_urls['https://']);
            unset($categories_urls['aboutus']);
            unset($categories_urls['testimonial']);
            unset($categories_urls['about-the-company']);
            unset($categories_urls['our-group-of-companies']);
            unset($categories_urls['our-values']);
            unset($categories_urls['corporate-video']);
            unset($categories_urls['testimonial']);
            unset($categories_urls['enquiry']);
            

        return  $categories_urls;

     }


  }

  public function getSupplierInfo($url=null){

    if($url){
     $domain=  $this->get_domain($url);
     if($domain!='www.indiamart.com'){
        $this->scrapCustomDomain($url);

     }else{
         
         $this->scrapIndiaMart($url);

     }
    }
  }

  private function get_domain($url){
      $pieces = parse_url($url);
      $domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
      if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
        return $regs['domain'];
      }
      return false;
  }



public function getURLs($keyword){

     $keyword=explode(" ",$keyword);
     $page_url=  implode("-",$keyword);
	 while($i<=10){
    		
        	$url="https://dir.indiamart.com/search.mp/next?ss=".$keyword."&c=IN&scroll=1&pr=0&pg=".$i."&frsc=19&_=1516683142036";
			$data=file_get_contents($url);

			$results=json_decode($data);
			$products=$results->content;
			
			file_put_contents("products/".$page_url, $products, FILE_APPEND | LOCK_EX);
			
	}

	return $page_url;

} 
public function getSuppliersLinks($page){

       $crawler = \Goutte::request('GET', 'http://localhost/wfm/public/products/'.$page.'.html');
      
       $nodeValues =  $crawler->filter('li')->each(function ($node)use ($page){
       	  
          if($node->filter('.pnm')->count()){
	          if($node->filter('.pnm')->count()){
	              $product['category']=$page;
	              $product['name']=$node->filter('.pnm')->text();
	              $product['product_url']=$node->filter('.pnm')->attr('href');
	            if($node->filter('.lcname')->count()){

		              $product['supplier_url']=$node->filter('.lcname')->attr('href');
		              $product['supplier_name']=$node->filter('.lcname')->text(); 

		          }


	          }
         

              return $product;              
          }
                      
         
        });
    
      
     
        foreach($nodeValues as $key=>$value)
        {
            if(is_null($value) || $value == '')
                unset($nodeValues[$key]);

        }
       $suppliers=[];
       foreach($nodeValues as $key=>$value)
        {
          if($value['product_url']!=null){

          	     $domain=  $this->get_domain($value['product_url']);
			     if($domain!='www.indiamart.com' && $domain!='indiamart.com'){
			     	     $temp= explode("#", $value['product_url']);
			            $suppliers[$temp[0]]=$temp[0];
			     }

          	
          }            

            
        }
         return  $suppliers ;

}


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function scrapByKeyword()
    {
	    ini_set('memory_limit', '-1');
	     //$keyword="modular kitchen";
	    // $page=  $this->getURLs($keyword); 
	     $page="modular-kitchen";
		 $suppliers= $this->getSuppliersLinks($page);
		 $products=[];
		 foreach($suppliers as $key=>$value){
		   $temp=	explode("/",$value);
		   $group_name= explode(".",$temp[count($temp)-1])[0];

		   $products[]=$this->scrapProductsByURL($value,$page,$group_name);
		  	sleep(4);
		 }
		echo json_encode($products); die();
    }

private function template1($crawler,$cate,$group){
	dd('template1');

}

private function template2($crawler,$cate,$group){

 	return $nodeValues =$crawler->filter('.p7.j.c1.m13.ul1.bx4.pr')->each(function ($node) use ($cate,$group){

		    if($node->filter("h2")->count())
		     $product['name']= $node->filter("h2")->text();
		   
		     if($product['name']){
		      $product['category']=$cate;
		      $product['group']=$group;
		     }
		    if($node->filter(".img.cpo")->count())
		     $product['image']= $node->filter(".scr.arw_no noscript img")->attr('src');
		    if($node->filter('.fl.w33 .plr')->count())
		    { 
		      $product['description']= $node->filter('.fl.w33 .plr')->html();
		      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
		    }elseif($node->filter('.fl.w33 .pll')->count())
		    { 
		      $product['description']= $node->filter('.fl.w33 .pll')->html();
		      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
		    }

		    if($node->filter('.m28.aprx .ff2.f18.c1')->count()){
			      $product['price']= $node->filter('.m28.aprx .ff2.f18.c1')->text();
			      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
			}elseif($node->filter('.m40.ds.fnt23 .fnt22.clr16')->count()){
			      $product['price']= $node->filter('.m40.ds.fnt23 .fnt22.clr16')->html();
			      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
			}
		    if($node->filter('.m28.aprx .ff2.f18.c1 .ff2.f12.c1')->count()){
			      $product['metric']= $node->filter('.m28.aprx .ff2.f18.c1 .ff2.f12.c1')->html();
			      $product['metric']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['metric']));
			}elseif($node->filter('.m40.ds.fnt23 .fnt23.clr19')->count()){
			      $product['metric']= $node->filter('.m40.ds.fnt23 .fnt23.clr19')->html();
			      $product['metric']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['metric']));
			}

		    
	

		    return $product;


    });
    



}

private function template3($crawler,$cate,$group){
 return $nodeValues =$crawler->filter('.cont6.bx2.w1.ds2.ps2')->each(function ($node) use ($cate,$group){

		    if($node->filter("h2")->count())
		     $product['name']= $node->filter("h2")->text();
		   
		     if($product['name']){
		      $product['category']=$cate;
		      $product['group']=$group;
		     }
		    if($node->filter(".ds2.m26 noscript img")->count())
		     $product['image']= $node->filter(".ds2.m26 noscript img")->attr('src');
		    if($node->filter('.he10.m4')->count())
		    { 
		      $product['description']= $node->filter('.he10.m4')->html();
		      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
		    }elseif($node->filter('.fl.w33 .pll')->count())
		    { 
		      $product['description']= $node->filter('.fl.w33 .pll')->html();
		      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
		    }
			if($node->filter('.fnt21.m37.ds.txt7.w27 .fnt18.clr16')->count()){
			      $product['price']= $node->filter('.fnt21.m37.ds.txt7.w27 .fnt18.clr16')->html();
			      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
			}elseif($node->filter('.m40.ds.fnt23 .fnt22.clr16')->count()){
			      $product['price']= $node->filter('.m40.ds.fnt23 .fnt22.clr16')->html();
			      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
			}
		    if($node->filter('.fnt21.m37.ds.txt7.w27 .fnt21.clr19')->count()){
			      $product['metric']= $node->filter('.fnt21.m37.ds.txt7.w27 .fnt21.clr19')->html();
			      $product['metric']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['metric']));
			}elseif($node->filter('.m40.ds.fnt23 .fnt23.clr19')->count()){
			      $product['metric']= $node->filter('.m40.ds.fnt23 .fnt23.clr19')->html();
			      $product['metric']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['metric']));
			}

			
	

		    return $product;


    });
    

 	

}

private function template4($crawler,$cate,$group){

	 	return $nodeValues =$crawler->filter('.w6.fl.bc1.hi3.m12.rq_btn.videoclass')->each(function ($node) use ($cate,$group){

		    if($node->filter("h2")->count())
		     $product['name']= $node->filter("h2")->text();
		   
		     if($product['name']){
		      $product['category']=$cate;
		      $product['group']=$group;
		     }
		    if($node->filter(".img.cpo")->count())
		     $product['image']= $node->filter(".scr.arw_no noscript img")->attr('src');
		    if($node->filter('.prn.f7.lh2.p19.tz.c7.wb.u1')->count())
		    { 
		      $product['description']= $node->filter('.prn.f7.lh2.p19.tz.c7.wb.u1')->html();
		      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
		    }elseif($node->filter('.fl.w33 .pll')->count())
		    { 
		      $product['description']= $node->filter('.fl.w33 .pll')->html();
		      $product['description']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['description']));
		    }

		    if($node->filter('.fn.prn .f10.c14')->count()){
			      $product['price']= $node->filter('.fn.prn .f10.c14')->text();
			      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
			}elseif($node->filter('.m40.ds.fnt23 .fnt22.clr16')->count()){
			      $product['price']= $node->filter('.m40.ds.fnt23 .fnt22.clr16')->html();
			      $product['price']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['price']));
			}
		    if($node->filter('.fn.prn .f6.c7')->count()){
			      $product['metric']= $node->filter('.fn.prn .f6.c7')->html();
			      $product['metric']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['metric']));
			}elseif($node->filter('.m40.ds.fnt23 .fnt23.clr19')->count()){
			      $product['metric']= $node->filter('.m40.ds.fnt23 .fnt23.clr19')->html();
			      $product['metric']=preg_replace('/[ \t \n]+/', ' ', preg_replace('/\s*$^\s*/m', "\n",  $product['metric']));
			}

		    
	

		    return $product;


    });
    

}

private function productTemplates(){

	$template['template1']=".w6_sh.m1.ds.m4_mn";
	$template['template2']=".p7.j.c1.m13.ul1.bx4.pr";
	$template['template3']=".cont6.bx2.w1.ds2.ps2";
	$template['template4']=".w6.fl.bc1.hi3.m12.rq_btn.videoclass";
    return $template;

}
 private   function scrapProductsByURL($url,$category,$group){

    	       
	   		$status=500;
	    	$count=0;	   
		    try{
			 	$user_agent=$this->userAgents();
			    $proxy=$this->ipAddress();
			    $client = new \Goutte\Client();
			    $client->setHeader('User-Agent',$user_agent);
			   
			   // $client->setClient(new \GuzzleHttp\Client(['proxy' =>$proxy ]));
			    $crawler = $client->request('GET',$url);

			    $status= $client->getResponse()->getStatus();
			}catch(\Exception $e){
				//print_r($e);
			}
			if($status==200){
								
				if($crawler->filterXpath('//input[@name="gluser_id"]')->count()){
				 	$supplier_id= $crawler->filterXpath('//input[@name="gluser_id"]')->attr('value');
				 $supplier_thumb="";  
                if($crawler->filterXpath('//meta[@name="twitter:image"]')->count()){
				    $supplier_thumb= $crawler->filterXpath('//meta[@name="twitter:image"]')->attr('content');
	            }
				    	$supplier_data=[];
					while(empty($supplier_data)){
					 	$ch = curl_init();
					     curl_setopt($ch, CURLOPT_URL,"https://www.indiamart.com/cgi/encode_decode_mobile.php");
					     curl_setopt($ch, CURLOPT_POST, 1);
					     curl_setopt($ch, CURLOPT_POSTFIELDS,
					            "glusrid=".$supplier_id."&modid=MDC");
					     curl_setopt($ch,CURLOPT_USERAGENT,$this->userAgents());
					   	 curl_setopt($ch, CURLOPT_PROXY, $this->ipAddress());
						 // receive server response ...
						 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$server_output = curl_exec ($ch);
						$supplier_data=json_decode($server_output, true);
				 }
				//	print_r($supplier_data);
				    $supplier_data['supplier_thumb']=$supplier_thumb;
				}
		        $templates=$this->productTemplates();
		        foreach($templates as $func=>$template){
		        	if($crawler->filter($template)->count()){
		            		$products=$this->$func($crawler,$category,$group);

		            		$productInfo['supplier']=$supplier_data;
		            		$productInfo['products']=$products;
		            		return $productInfo;
		        	}

		        }
		 
				
			}
	        
	  


	}


}
