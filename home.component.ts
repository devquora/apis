import { Component, OnInit,PLATFORM_ID,Inject} from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import {  Constants } from "../services/constants";
import {  Router,ActivatedRoute} from '@angular/router';
import { Meta, Title } from "@angular/platform-browser";
import {AlertService,WallService,AuthenticationService,QuestionService} from '../services/index'

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {
	
	model: any = {};
    loading = false;
    popularWalls: any = [];
	latestQuestions: any = [];
    backend_url: string = Constants.API_END_POINT;
	public url = Constants.API_END_POINT+'/api/search';
	public api = 'http';
	public params = {
		xhr: 't',
		type:'articles'
	};
	
	
	public search = '';
    createQuery(){
		this.params.type=this.model.search_type;
	}
	handleResultSelected (result) {
		this.search = result;
		this.model.query=result.title;
		if(this.model.search_type!='link'){
		 this.router.navigate([this.model.search_type+'/'+result.slug]);
		}else{
		  this.router.navigate(['articles/'+result.slug]);
		}
		
	}

   constructor(
        private router: Router,
        private wallService: WallService,
		private alertService: AlertService,
		private questionService: QuestionService,
        private route: ActivatedRoute,
		@Inject(PLATFORM_ID) private platformId: Object,
        private authenticationService: AuthenticationService,
		private meta: Meta,
		private title: Title
		) { }

   ngOnInit(){
	    this.model.search_type="articles";
	    this.title.setTitle('Top frameworks PHP, Javascript , java , .net | Devquora');

		this.meta.addTags([
		  { name: 'author',   content: 'devquora.com'},
		  { name: 'keywords', content: 'Laravel Crud, Angular Crud , Node , React Js CRUD, Codeigniter , cakephp, Vue js ,PHP , Java , Tutorials'},
		  { name: 'description', content: 'Devquora is an online network of developers basically a (Developer 2 Developer Network) for solving day to day common code problems of software developer and programmers.We provide starting tutorials and interview questions on various programming lanaguages and Frameworks' },
		 
		]);
		
	       if (isPlatformBrowser(this.platformId)) {	
		    	window.scrollTo(0, 0);
         	} 
	  		this.wallService.getPopularWalls()
			.subscribe(
				data => {
					this.popularWalls = data;
					for(var i = 0; i< this.popularWalls.length; i++){
					 this.wallService.getBySlug(this.popularWalls[i].slug).subscribe(
						data => {
						//console.log("cached");
						},
						error => {
						this.alertService.error(error);
						});
				}
					
				},
				error => {
					this.alertService.error(error);
				});
	
			this.questionService.getLatestQuestions()
			.subscribe(
				data => {
					this.latestQuestions = data.data;
				},
				error => {
					this.alertService.error(error);
				});
			
    }

}
