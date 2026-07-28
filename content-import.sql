-- Imported blog posts from the previous billykulpa.com
-- Import AFTER schema.sql. Safe to re-run: wipes and reloads all posts.
-- (NOTE: this deletes any posts written in the admin — export those
-- first if you've started blogging in the new CMS.)
-- body_html preserves the original markup exactly; body_md is the
-- editable markdown equivalent (raw HTML blocks pass through).

DELETE FROM posts;

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('about-this-website', 'What is This Website Even For?', 'What is This Website Even For?', 'This is a blog post summarizing my goals for billykulpa.com.', 'I have owned this domain since 2007.

Impossibly, that\'s almost half my life. And in all that time, I\'ve never had a proper website. Or at least something I have been proud of.

*So here we are.* I\'ve got a website up. What\'s the goal?

I guess my primary objective is to have a home for my portfolio. There are bits of my work all over the web because I\'ve never done a good job of curating. My newspaper designs used to live at [Carbonmade](https://billykulpa.carbonmade.com/projects/23095) before they kicked everyone off of the free plan. My music is on [Soundcloud](https://soundcloud.com/billykulpa). I have a ton of icons and other one-off elements on [Dribbble](https://dribbble.com/billykulpa). And so on.

I\'d also like to blog more. Like all blogs, my posts are going to seem kind of random. You might find a review of a 20-year old album I\'ve recently started listening to again. Or more likely, reflections on raising two sons with autism. I don\'t particularly care if anyone reads it. The blog is for me.

This site is absolutely going to mix the personal and the professional.

And finally, I\'d like a place to experiment with my code. This site, as a whole, is an experiment in optimization. It utilizes a couple of variable Google fonts -- the sans-serif is [Sora](https://fonts.google.com/specimen/Sora "Find Sora on Google Fonts") and the serif is [Domine](https://fonts.google.com/specimen/Domine "Find Domine on Google Fonts"). There is minimal vanilla JavaScript. There are no JPGs to be found. There is no CMS.

Above all else, I avoid publishing a page if I can\'t secure a 100 on [Google PageSpeed Insights](https://pagespeed.web.dev/report?url=https%3A%2F%2Fwww.billykulpa.com%2F). Sometimes it can\'t be helped, but you can check this little page score link at the bottom of any page to see if I succeeded or not.', '<p>I have owned this domain since 2007.</p>
<p>Impossibly, that\'s almost half my life. And in all that time, I\'ve never had a proper website. Or at least something I have been proud of.</p>
<p><em>So here we are.</em> I\'ve got a website up. What\'s the goal?</p>
<p>I guess my primary objective is to have a home for my portfolio. There are bits of my work all over the web because I\'ve never done a good job of curating. My newspaper designs used to live at <a data-id="https://billykulpa.carbonmade.com/projects/23095" data-type="URL" href="https://billykulpa.carbonmade.com/projects/23095" rel="noreferrer noopener" target="_blank">Carbonmade</a> before they kicked everyone off of the free plan. My music is on <a href="https://soundcloud.com/billykulpa" rel="noreferrer noopener" target="_blank">Soundcloud</a>. I have a ton of icons and other one-off elements on <a href="https://dribbble.com/billykulpa" rel="noreferrer noopener" target="_blank">Dribbble</a>. And so on.</p>
<p>I\'d also like to blog more. Like all blogs, my posts are going to seem kind of random. You might find a review of a 20-year old album I\'ve recently started listening to again. Or more likely, reflections on raising two sons with autism. I don\'t particularly care if anyone reads it. The blog is for me.</p>
<p>This site is absolutely going to mix the personal and the professional.</p>
<p>And finally, I\'d like a place to experiment with my code. This site, as a whole, is an experiment in optimization. It utilizes a couple of variable Google fonts -- the sans-serif is <a href="https://fonts.google.com/specimen/Sora" target="_blank" title="Find Sora on Google Fonts">Sora</a> and the serif is <a href="https://fonts.google.com/specimen/Domine" target="_blank" title="Find Domine on Google Fonts">Domine</a>. There is minimal vanilla JavaScript. There are no JPGs to be found. There is no CMS.</p>
<p>Above all else, I avoid publishing a page if I can\'t secure a 100 on <a href="https://pagespeed.web.dev/report?url=https%3A%2F%2Fwww.billykulpa.com%2F" rel="noreferrer noopener" target="_blank">Google PageSpeed Insights</a>. Sometimes it can\'t be helped, but you can check this little page score link at the bottom of any page to see if I succeeded or not.</p>', 'published', '2022-05-26 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('a-video-time-capsule', 'A Video Time Capsule', 'A Video Time Capsule', 'I found an on-camera interview I did from almost 20 years ago.', '<div class="video-container standard">
<lite-vimeo style="background-image: url(\'https://i.vimeocdn.com/video/810965406.webp?mw=1600&amp;mh=900&amp;q=70\');" videoid="716789842">
<div class="ltv-playbtn"></div>

While digging through old work for my portfolio, I came across this grainy 360p video I did while working at the Rockford Register Star. Management was pushing publishing to the web *before* the story appeared in print, which was implausibly controversial at the time.

The video was supposed to be for just the newsroom, but I see that it got posted to YouTube on April 25, 2008 and now has something like 7,000 views. So here we are.

I miss being this young, fit, and earnest, for sure, but there\'s plenty of awkwardness there, too.

One other note: I remember losing the battle of shooting this in 16x9 because *4x3* was still the standard in that era (!).

Everything about this video seems impossible.', '<div class="video-container standard">
<lite-vimeo style="background-image: url(\'https://i.vimeocdn.com/video/810965406.webp?mw=1600&amp;mh=900&amp;q=70\');" videoid="716789842">
<div class="ltv-playbtn"></div>
</lite-vimeo>
<script async="" src="https://cdn.jsdelivr.net/npm/lite-vimeo-embed/+esm" type="module"></script>
</div>
<p>While digging through old work for my portfolio, I came across this grainy 360p video I did while working at the Rockford Register Star. Management was pushing publishing to the web <em>before</em> the story appeared in print, which was implausibly controversial at the time.</p>
<p>The video was supposed to be for just the newsroom, but I see that it got posted to YouTube on April 25, 2008 and now has something like 7,000 views. So here we are.</p>
<p>I miss being this young, fit, and earnest, for sure, but there\'s plenty of awkwardness there, too.</p>
<p>One other note: I remember losing the battle of shooting this in 16x9 because <em>4x3</em> was still the standard in that era (!).</p>
<p>Everything about this video seems impossible.</p>', 'published', '2022-06-03 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('new-superhero-center-for-autism-website-launched', 'New Superhero Center for Autism Website Launched', 'New Superhero Center for Autism Website Launched', 'I launched a new website for the Superhero Center for Autism', '<figure>
<picture>


<img alt="The new website of the Superhero Center for Autism" height="1175" src="/assets/img/homepage-superhero-center-for-autism-launch-1920x1175.webp" width="1920"/>
</picture>
<figcaption>The new website of the Superhero Center for Autism at the time of launch.</figcaption>
</figure>

After something like nine months of promising to get it done, I got a new [Superhero Center for Autism](https://www.superherocenter.org/) launched last weekend.

I think the site is a real step in the right direction! I\'m proud of the brand motif, which I mostly created on the fly. The colorful angled bars and monochrome Ben Day dots let me avoid the [horribly offensive puzzle piece motif](https://www.altogetherautism.org.nz/autism-no-puzzle-nothing-wrong-with-us/) that so many autism organizations fall into. We\'re going to be able to use those elements in our printed materials and on social media going forward.

And I\'m even more proud of the 100 score the site is getting on [Google PageSpeed Insights](https://pagespeed.web.dev/report?url=https%3A%2F%2Fwww.superherocenter.org%2F) -- on both mobile and desktop.

The best part of the site is that outside of my time commitment, it\'s practically free. [DreamHost donates shared web server space](https://help.dreamhost.com/hc/en-us/articles/215769478-Non-profit-discount) to non-profits, so all the center has to pay for is the annual domain renewal.

The website itself has a few holes still. We need WooCommerce store for the center\'s t-shirts, hoodies, and bracelets. And I need to hire a photographer to get nice photos of the center being used so we can replace the stock photography and the empty building gallery. But there\'s always more work, no matter what the project.

I\'m going to fill out the site with a bit more content and media over the rest of 2022. It\'s tricky to find the time to do pretty much *anything* when you\'re the parent of children with autism. We try to get my boys in bed by 8, but most nights that can easily stretch until 9. Do you have any idea how hard it is to start fixing bugs in like, mobile navigation, at 9:45 p.m. on a Tuesday? I assure you it\'s impossible.

My immediate next steps are getting the old site taken down and the domain forwarded to the new one. I suspect that it will take some time because apparently [Jimdo holds domains hostage](https://help.jimdo.com/hc/en-us/articles/115005533903-How-do-I-transfer-my-domain-to-another-host-#:~:text=Head%20to%20Questions%20in%20the,do%20with%20your%20Jimdo%20contract.). I\'ve emailed them twice so far asking for an authorization code, so who knows.

But it\'s hard to stay mad. I feel great about getting the project launched. Onward!', '<figure>
<picture>


<img alt="The new website of the Superhero Center for Autism" height="1175" src="/assets/img/homepage-superhero-center-for-autism-launch-1920x1175.webp" width="1920"/>
</picture>
<figcaption>The new website of the Superhero Center for Autism at the time of launch.</figcaption>
</figure>
<p>After something like nine months of promising to get it done, I got a new <a href="https://www.superherocenter.org/" rel="noreferrer noopener" target="_blank">Superhero Center for Autism</a> launched last weekend.</p>
<p>I think the site is a real step in the right direction! I\'m proud of the brand motif, which I mostly created on the fly. The colorful angled bars and monochrome Ben Day dots let me avoid the <a href="https://www.altogetherautism.org.nz/autism-no-puzzle-nothing-wrong-with-us/" rel="noreferrer noopener" target="_blank">horribly offensive puzzle piece motif</a> that so many autism organizations fall into. We\'re going to be able to use those elements in our printed materials and on social media going forward.</p>
<p>And I\'m even more proud of the 100 score the site is getting on <a href="https://pagespeed.web.dev/report?url=https%3A%2F%2Fwww.superherocenter.org%2F" rel="noreferrer noopener" target="_blank">Google PageSpeed Insights</a> -- on both mobile and desktop.</p>
<p>The best part of the site is that outside of my time commitment, it\'s practically free. <a href="https://help.dreamhost.com/hc/en-us/articles/215769478-Non-profit-discount" rel="noreferrer noopener" target="_blank">DreamHost donates shared web server space</a> to non-profits, so all the center has to pay for is the annual domain renewal.</p>
<p>The website itself has a few holes still. We need WooCommerce store for the center\'s t-shirts, hoodies, and bracelets. And I need to hire a photographer to get nice photos of the center being used so we can replace the stock photography and the empty building gallery. But there\'s always more work, no matter what the project.</p>
<p>I\'m going to fill out the site with a bit more content and media over the rest of 2022. It\'s tricky to find the time to do pretty much <em>anything</em> when you\'re the parent of children with autism. We try to get my boys in bed by 8, but most nights that can easily stretch until 9. Do you have any idea how hard it is to start fixing bugs in like, mobile navigation, at 9:45 p.m. on a Tuesday? I assure you it\'s impossible.</p>
<p>My immediate next steps are getting the old site taken down and the domain forwarded to the new one. I suspect that it will take some time because apparently <a href="https://help.jimdo.com/hc/en-us/articles/115005533903-How-do-I-transfer-my-domain-to-another-host-#:~:text=Head%20to%20Questions%20in%20the,do%20with%20your%20Jimdo%20contract." rel="noreferrer noopener" target="_blank">Jimdo holds domains hostage</a>. I\'ve emailed them twice so far asking for an authorization code, so who knows.</p>
<p>But it\'s hard to stay mad. I feel great about getting the project launched. Onward!</p>', 'published', '2022-06-21 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('my-1994-appearance-in-the-weekly-world-news', 'My 1994 Appearance in the Weekly World News', 'My 1994 Appearance in the Weekly World News', 'I once appeared in the Weekly World News as a missing hide-and-seeker', 'So this story is 100% true as my memory will permit.

<figure>
<picture>


<img alt="My appearance in the Weekly World News, where I was listed as Ole Lykke" height="1280" src="/assets/img/weekly-world-news-ole-lykke-billy-kulpa-1280x1436.webp" width="1436"/>
</picture>
<figcaption>That\'s me, Ole Lykke, in the top left of the page. The rest of the missing kids are my siblings and cousins. The (terrible) seeker, Jon Delgren, is actually my step-brother, Adam Reum.</figcaption>
</figure>

When I was a kid, I had an uncle who worked for the [Weekly World News](https://weeklyworldnews.com/ "The website of the Weekly World News"). His name was [Dick Kulpa](https://en.wikipedia.org/wiki/Dick_Kulpa "Read about my Uncle Rick on Wikipedia"), and he was employed as their art director or managing editor for more than a decade.

Sadly, [he died in 2021](https://www.narratively.com/p/the-weird-wacky-and-wild-ride-of-captain-cartoon-father-of-bat-boy "Read about the passing of Dick Kulpa").

He was a bit of an [eccentric dude](https://web.archive.org/web/20160613232921/http://history.rockfordpubliclibrary.org/localhistory/?tag=dick-kulpa "My Uncle Rick was very much a character") who is probably [most famous for inventing Bat Boy](https://www.vice.com/read/an-interview-with-the-creator-of-bat-boy-987 "Read more about how my uncle Rick invented Bay Boy").

I was born in raised in the Rockford, Illinois, area. The Weekly World News was based somewhere in Florida. Naturally, I didn\'t see much of my uncle growing up. We never were particularly close, which is a shame because we ended up working similar careers and sharing similar creative aspirations.

My uncle was a bit of a ghost story among my family ("The Successful One"). He\'d come back every so often for holidays and weddings and funerals, but we mostly never saw him. One of my most vivid memories is the time he raced the number zero car — drawn as a donut on both doors and on the roof — at the [Rockford Speedway](https://www.rockfordspeedway.com/ "The website of the Rockford Speedway, which sadly closed in 2023"). As I recall, he won a feature heat and finished second in the demolition derby to close out the night. I might have that backwards.

The guy drove race cars in demo derbies. He was as close to a hero as I had as a 10-year-old kid.

Around Christmas or Easter in 1993 or 1994, my uncle tells the family that he\'s pitched a story to the News about a game of hide-and-seek gone bad. He wants to use photos of my siblings, cousins, and me as the kids in the story. Everyone agrees that this is the greatest idea of all time.

In case you didn\'t know, all the stories in the Weekly World News are made up. Unless you believe [Men in Black](https://www.giantfreakinrobot.com/scifi/men-black-3-posters-kick-viral-marketing-campaign.html "The Weekly World News was featured in the film Men in Black").

The issue hit the stands in the fall of 1994. One cousin ended up getting cut from the feature. I texted him tonight to get his memories about the story and he admitted to crying when he found out he was cut. To be fair, he was seven.

So that\'s the story of how I ended up in the Weekly World News as a missing 10 year old named Ole Lykke. I\'ve attached the image of the story to this post. [And here\'s another link to the Google Books search result](https://books.google.com/books?id=RPIDAAAAMBAJ&pg=PA11&lpg=PA11&dq=hide+and+seek+missing+weekly+world+news&source=bl&ots=JZiyp8UCn1&sig=PHvYeQz8IbKxcbfg7ImOqJwmfi4&hl=en&sa=X&ved=0CB8Q6AEwAGoVChMImvaf6aiqxwIVjHuSCh1f_wbT#v=onepage&q=hide%20and%20seek%20missing%20weekly%20world%20news&f=false "Read the original article on Google Books"), which lets you zoom in pretty far.

Happy reading.', '<p>So this story is 100% true as my memory will permit.</p>
<figure>
<picture>


<img alt="My appearance in the Weekly World News, where I was listed as Ole Lykke" height="1280" src="/assets/img/weekly-world-news-ole-lykke-billy-kulpa-1280x1436.webp" width="1436"/>
</picture>
<figcaption>That\'s me, Ole Lykke, in the top left of the page. The rest of the missing kids are my siblings and cousins. The (terrible) seeker, Jon Delgren, is actually my step-brother, Adam Reum.</figcaption>
</figure>
<p>When I was a kid, I had an uncle who worked for the <a href="https://weeklyworldnews.com/" target="_blank" title="The website of the Weekly World News">Weekly World News</a>. His name was <a href="https://en.wikipedia.org/wiki/Dick_Kulpa" target="_blank" title="Read about my Uncle Rick on Wikipedia">Dick Kulpa</a>, and he was employed as their art director or managing editor for more than a decade.</p>
<p>Sadly, <a href="https://www.narratively.com/p/the-weird-wacky-and-wild-ride-of-captain-cartoon-father-of-bat-boy" target="_blank" title="Read about the passing of Dick Kulpa">he died in 2021</a>.</p>
<p>He was a bit of an <a href="https://web.archive.org/web/20160613232921/http://history.rockfordpubliclibrary.org/localhistory/?tag=dick-kulpa" rel="noreferrer noopener" target="_blank" title="My Uncle Rick was very much a character">eccentric dude</a> who is probably <a href="https://www.vice.com/read/an-interview-with-the-creator-of-bat-boy-987" target="_blank" title="Read more about how my uncle Rick invented Bay Boy">most famous for inventing Bat Boy</a>.</p>
<p>I was born in raised in the Rockford, Illinois, area. The Weekly World News was based somewhere in Florida. Naturally, I didn\'t see much of my uncle growing up. We never were particularly close, which is a shame because we ended up working similar careers and sharing similar creative aspirations.</p>
<p>My uncle was a bit of a ghost story among my family ("The Successful One"). He\'d come back every so often for holidays and weddings and funerals, but we mostly never saw him. One of my most vivid memories is the time he raced the number zero car — drawn as a donut on both doors and on the roof — at the <a href="https://www.rockfordspeedway.com/" target="_blank" title="The website of the Rockford Speedway, which sadly closed in 2023">Rockford Speedway</a>. As I recall, he won a feature heat and finished second in the demolition derby to close out the night. I might have that backwards.</p>
<p>The guy drove race cars in demo derbies. He was as close to a hero as I had as a 10-year-old kid.</p>
<p>Around Christmas or Easter in 1993 or 1994, my uncle tells the family that he\'s pitched a story to the News about a game of hide-and-seek gone bad. He wants to use photos of my siblings, cousins, and me as the kids in the story. Everyone agrees that this is the greatest idea of all time.</p>
<p>In case you didn\'t know, all the stories in the Weekly World News are made up. Unless you believe <a href="https://www.giantfreakinrobot.com/scifi/men-black-3-posters-kick-viral-marketing-campaign.html" target="_blank" title="The Weekly World News was featured in the film Men in Black">Men in Black</a>.</p>
<p>The issue hit the stands in the fall of 1994. One cousin ended up getting cut from the feature. I texted him tonight to get his memories about the story and he admitted to crying when he found out he was cut. To be fair, he was seven.</p>
<p>So that\'s the story of how I ended up in the Weekly World News as a missing 10 year old named Ole Lykke. I\'ve attached the image of the story to this post. <a href="https://books.google.com/books?id=RPIDAAAAMBAJ&amp;pg=PA11&amp;lpg=PA11&amp;dq=hide+and+seek+missing+weekly+world+news&amp;source=bl&amp;ots=JZiyp8UCn1&amp;sig=PHvYeQz8IbKxcbfg7ImOqJwmfi4&amp;hl=en&amp;sa=X&amp;ved=0CB8Q6AEwAGoVChMImvaf6aiqxwIVjHuSCh1f_wbT#v=onepage&amp;q=hide%20and%20seek%20missing%20weekly%20world%20news&amp;f=false" target="_blank" title="Read the original article on Google Books">And here\'s another link to the Google Books search result</a>, which lets you zoom in pretty far.</p>
<p>Happy reading.</p>', 'published', '2022-07-07 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('superhero-center-featured-in-northwest-quarterly-magazine', 'Superhero Center Featured in the Northwest Quarterly', 'Superhero Center Featured in the Northwest Quarterly', 'The Superhero Center for Autism was featured in a Northwest Quarterly article.', '<figure>
<picture>


<img alt="Jude and his friend Hannah playing a Kirby video game at the Superhero Center for Autism" height="576" src="/assets/img/jude-kulpa-superhero-center-1000x800.webp" width="720"/>
</picture>
<figcaption>My youngest son, Jude, playing Kirby with his friend at the Superhero Center for Autism.</figcaption>
</figure>

The [Northwest Quarterly](https://oldnorthwestterritory.northwestquarterly.com/2023/04/17/the-superhero-center-for-autism-where-kids-find-their-inner-superhero/) put out a really nice feature on the [Superhero Center for Autism](https://www.superherocenter.org/). I\'ve been the president of the center for about a year, so it was gratifying to see such a positive look at the organization\'s mission and history.

The article gets into what we do at the center, as well as our history and a top-level look at autism spectrum disorder.

Plus there were a couple of really neat photos of my kids published. Check out Jude, above, playing Kirby for the Super Nintendo, or the photo of Grant, below, playing on the center\'s swings.

<figure>
<picture>


<img alt="My oldest son, Grant, hanging from the industrial swings at the Superhero Center for Autism" height="576" src="/assets/img/grant-kulpa-superhero-center-1000x800.webp" width="720"/>
</picture>
<figcaption>My oldest son, Grant, hanging from the industrial swings at the Superhero Center for Autism.</figcaption>
</figure>

The center is run entirely by volunteers, and all of our funds are raised through donations and fundraisers. If you\'d like to help with either, visit our website at [superherocenter.org](https://www.superherocenter.org "Visit the website of the Superhero Center for Autism").', '<figure>
<picture>


<img alt="Jude and his friend Hannah playing a Kirby video game at the Superhero Center for Autism" height="576" src="/assets/img/jude-kulpa-superhero-center-1000x800.webp" width="720"/>
</picture>
<figcaption>My youngest son, Jude, playing Kirby with his friend at the Superhero Center for Autism.</figcaption>
</figure>
<p>The <a href="https://oldnorthwestterritory.northwestquarterly.com/2023/04/17/the-superhero-center-for-autism-where-kids-find-their-inner-superhero/" rel="noreferrer noopener" target="_blank">Northwest Quarterly</a> put out a really nice feature on the <a href="https://www.superherocenter.org/" rel="noreferrer noopener" target="_blank">Superhero Center for Autism</a>. I\'ve been the president of the center for about a year, so it was gratifying to see such a positive look at the organization\'s mission and history.</p>
<p>The article gets into what we do at the center, as well as our history and a top-level look at autism spectrum disorder.</p>
<p>Plus there were a couple of really neat photos of my kids published. Check out Jude, above, playing Kirby for the Super Nintendo, or the photo of Grant, below, playing on the center\'s swings.</p>
<figure>
<picture>


<img alt="My oldest son, Grant, hanging from the industrial swings at the Superhero Center for Autism" height="576" src="/assets/img/grant-kulpa-superhero-center-1000x800.webp" width="720"/>
</picture>
<figcaption>My oldest son, Grant, hanging from the industrial swings at the Superhero Center for Autism.</figcaption>
</figure>
<p>The center is run entirely by volunteers, and all of our funds are raised through donations and fundraisers. If you\'d like to help with either, visit our website at <a href="https://www.superherocenter.org" target="_blank" title="Visit the website of the Superhero Center for Autism">superherocenter.org</a>.</p>', 'published', '2023-04-19 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('explaining-walter-matthau-disease', 'Explaining Walter Matthau Disease', 'Explaining Walter Matthau Disease', 'A blog post discussing the origins of Walter Matthau Disease.', '<figure>
<picture>


<img alt="Walter Matthau, the star of 1974\'s The Taking of Pelham One Two Three" height="1080" src="/assets/img/walter-matthau-the-taking-of-pelham-one-two-three.webp" width="1920"/>
</picture>
<figcaption>Walter Matthau, tragically seen here at just 22 years old.</figcaption>
</figure>

Back in 2015, I started a movie podcast with former [Rockford Register Star](https://www.rrstar.com/ "Visit the website of the Rockford Register Star") colleagues [Will Pfeifer](https://www.pfeiferland.com/ "Visit the website of Will Pfeifer"), Kevin Haas, and Ben Stanley. The podcast, called [Out of Theaters](https://www.outoftheaters.com/ "Visit the website of Out of Theaters"), had a simple premise: Will would introduce old ("""classic""") films to his younger co-hosts, and then explain to us how and why we were simpletons for not appreciating the film.

It was a fun and intensely time-consuming project.

We did a couple of pre-production test episodes which were never been released to the public. That was for good reason: They were awful. We shared a single mic. We had no format or structure. It was a free-for-all. But the enduring legacy of those tests? The concept of **Walter Matthau Disease**.

One of films we tested was 1974\'s *The Taking of Pelham One Two Three*, which stars the aforementioned Matthau. I don\'t remember much of the plot of that movie. I\'m sure I said it was fine. But what I *can* tell you is that despite being 53 years young at the time of filming, Matthau looks exactly the same as I had always known him. There was essentially no difference between Matthau\'s appearance in the early 1970s and his appearance in the late 1990s.

Once we started talking about the concept, we realized there are many other people who suffer the same debilitating condition. Eugene Levy. Betty White. Morgan Freeman. James Cromwell. Steve Martin. Even Matthau\'s *The Taking of Pelham One Two Three* co-star Jerry Stiller suffered from Walter Matthau Disease.

Once you\'re aware of this serious and unfortunate condition, you\'ll see it everywhere. Please do what you can to spread awareness.', '<figure>
<picture>


<img alt="Walter Matthau, the star of 1974\'s The Taking of Pelham One Two Three" height="1080" src="/assets/img/walter-matthau-the-taking-of-pelham-one-two-three.webp" width="1920"/>
</picture>
<figcaption>Walter Matthau, tragically seen here at just 22 years old.</figcaption>
</figure>
<p>Back in 2015, I started a movie podcast with former <a href="https://www.rrstar.com/" target="_blank" title="Visit the website of the Rockford Register Star">Rockford Register Star</a> colleagues <a href="https://www.pfeiferland.com/" target="_blank" title="Visit the website of Will Pfeifer">Will Pfeifer</a>, Kevin Haas, and Ben Stanley. The podcast, called <a href="https://www.outoftheaters.com/" target="_blank" title="Visit the website of Out of Theaters">Out of Theaters</a>, had a simple premise: Will would introduce old ("""classic""") films to his younger co-hosts, and then explain to us how and why we were simpletons for not appreciating the film.</p>
<p>It was a fun and intensely time-consuming project.</p>
<p>We did a couple of pre-production test episodes which were never been released to the public. That was for good reason: They were awful. We shared a single mic. We had no format or structure. It was a free-for-all. But the enduring legacy of those tests? The concept of <strong>Walter Matthau Disease</strong>.</p>
<p>One of films we tested was 1974\'s <em>The Taking of Pelham One Two Three</em>, which stars the aforementioned Matthau. I don\'t remember much of the plot of that movie. I\'m sure I said it was fine. But what I <em>can</em> tell you is that despite being 53 years young at the time of filming, Matthau looks exactly the same as I had always known him. There was essentially no difference between Matthau\'s appearance in the early 1970s and his appearance in the late 1990s.</p>
<p>Once we started talking about the concept, we realized there are many other people who suffer the same debilitating condition. Eugene Levy. Betty White. Morgan Freeman. James Cromwell. Steve Martin. Even Matthau\'s <em>The Taking of Pelham One Two Three</em> co-star Jerry Stiller suffered from Walter Matthau Disease.</p>
<p>Once you\'re aware of this serious and unfortunate condition, you\'ll see it everywhere. Please do what you can to spread awareness.</p>', 'published', '2024-03-19 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('recovering-a-lost-spotlight-article-from-college', 'Recovering a Lost Spotlight Article from College', 'Recovering a Lost Spotlight Article from College', 'Northern Illinois University spotlight on Billy Kulpa', 'Back in 2006, [Mark McGowan](https://cedu.news.niu.edu/author/mmcgowanniu-edu/ "Visit the author page of Mark McGowan") at Northern Illinois University did a ["Huskie Spotlight"](https://www.niu.edu/spotlight/index.shtml "Visit the Northern Illinois University Huskie Spotlight landing page") article on me after I led a redesign of the university\'s student newspaper.

I had just earned a design fellowship at the prestigious Poynter Institute for Media Studies and was gaining traction in the (admittedly tiny) news design community. My newspaper adviser, [Jim Killam](https://www.linkedin.com/in/jim-killam-478b552/ "Find Jim Killam on LinkedIn"), was kind enough to alert NIU\'s public relations team.

It\'s been two decades since the article was published, so naturally, it no longer exists on [NIU\'s website](https://www.niu.edu/ "Visit the website of Northern Illinois University"). I am duplicating it here for posterity.

> ## Northern Star Designer Scores Poynter Fellowship
>
> - By Mark McGowan, Northern Illinois University
>
> Not everyone can say a radio changed their life, but not everyone is Billy Kulpa.
>
> Kulpa, a high school graduate without a plan, listened to 12 long hours of sports talk each day while he detailed cars at a Rockford auto dealership. One day, working on his knees, he heard ESPN Radio\'s Dan Patrick read an e-mail on air that asked the famous sports broadcaster how to become, well, a famous sports broadcaster.
>
> Patrick\'s response: Learn to write.
>
> Intrigued and empowered, Kulpa enrolled at Rock Valley College and took a job with The Valley Forge, the campus newspaper.
>
> Only one semester later, and with more enthusiasm than experience, he became sports editor. Higher jobs followed, including managing editor his second year and editor-in-chief his third. With those positions came a multitude of responsibilities beyond writing, one of which was the paper\'s design.
>
> And so began a love affair that has catapulted Kulpa to the top of the nation\'s collegians pursuing careers in visual journalism.
>
> Kulpa is one of 32 students nationwide to win a coveted Poynter Summer Fellowship for Young Journalists. He\'ll attend the prestigious institute in Florida from June 3 to July 13; three days later, he begins a two-month design internship with the Orlando Sentinel.
>
> "I got into design, and I started getting better. I wanted to be good at it," says Kulpa, downing a store-bought Rice Krispies treat and a 20-ounce Mountain Dew for breakfast.
>
> "It\'s not something everyone knows about — designing newspapers — and I had to learn everything for myself," he adds. "It\'s a job where I excel. I like the creativity. There\'s not really one way to do things. Some people equate it to putting together a puzzle, but it\'s not. There\'s not just one solution."
>
> Northern Star adviser Jim Killam says Kulpa is independent, driven and passionate.
>
> "He\'s really discovered what motivates him, and that\'s visual journalism. He\'s figured out that he\'s very good at it, and we\'ve had it confirmed by some national organizations and by people at some pretty important newspapers," Killam says. "Billy is very good at looking at what\'s going on in the world of news design, spotting trends and spotting newspapers the Star can emulate or improve on in some way."
>
> Kulpa is eager for what Poynter will bring, what he calls "a master\'s degree in six weeks."
>
> "I\'m living here in the cornfields, and I\'m pretty good at what I do in DeKalb," he says. "What they offer at Poynter is a perspective from the best journalists in the world. It\'s a tremendous privilege. It\'s unbelievable."
>
> He was offered his fellowship while in Flordia competing in "The Intern," a contest sponsored by the Society for News Design.
>
> Admission to "The Intern" contest was based on a 500-word essay, a 60-second video and a "campaign poster." Once there, he and nine others were placed on teams and matched up in a competition modeled after reality TV programs.
>
> A first-round challenge involved creating a news page at the Sentinel offices.
>
> Returned to their hotel rooms near midnight, they were awoken only four hours later by contest organizers pounding on the doors. Osama bin Laden is dead, the contestants were told, and all their front pages needed immediate renovation.
>
> Another challenge sent them to Disney World, where they were handed cameras and lists of photos to shoot that visually expressed concepts such as love, happy, frilly or about a dozen other things. Kulpa\'s photos later were given to another contestant to use; he received another competitor\'s snapshots for his page.
>
> When the pool was cut to five, Kulpa was still alive.
>
> A Thursday session opened with a cryptic instruction: Pay attention. Martin Gee, a designer at the San Jose Mercury News, gave the keynote address.
>
> Afterward, more than two dozen students who had applied that week for Poynter fellowships were given 20 minutes to create graphics — "charticles," Kulpa calls them — that expressed what they\'d learned that morning.
>
> Kulpa\'s compared "Where I Am" to "Where I Need to Be."
>
> "His entry was most successful, based on our specific instructions," wrote Sara Quinn, a member of the visual journalism faculty at Poynter. "His writing was very witty and concise — consistently so throughout his profile."
>
> After winning one of the prized fellowships, which would conflict with the scheduling of his internship, he called Killam for advice. "Take the fellowship!" Killam exclaimed.
>
> Editors at the Sentinel still wanted him, fortunately, and pushed back his start date.
>
> "It was a crazy, crazy hard week," Kulpa says.
>
> At NIU, Kulpa is best known (or perhaps unknown) as the behind-the-scenes guy partially responsible for last January\'s bold new look of the Northern Star.
>
> Red and orange? Kulpa\'s favorite colors.
>
> He played that same role again through the summer with new editors Justin Smith and Steve Brown as the unconventional motif was refined and repackaged, returning some elements of traditional newspaper design with the modern concept that the paper should resemble a Web page.
>
> The original decision to overhaul the Star\'s design came quickly and began just as quickly, Kulpa says. He and then-editor Derek Wright initiated their work in the middle of December 2005 for a mid-January 2006 launch.
>
> "Our product looked dated," Kulpa says. "We were stuck with things we didn\'t use and things our readers didn\'t use."
>
> The pair planned to take their time. A conversation with Mike Kellams, sports editor at the Chicago Tribune and former designer of the Tribune\'s RedEye, changed their minds. With only a few semesters of college left, Kellams asked, why wait?
>
> "He said, ‘Just do it,\' and he was right," Kulpa says.
>
> So they started from scratch. "For 12 hours a day we yelled at each other — in a good way, a healthy way," Kulpa says. "I don\'t think we realized the magnitude of it at the time."
>
> The results startled the campus community, which wasn\'t sure what to think, and the Star\'s alumni, who praised the innovation.
>
> It also won awards, including one from the Student Society for News Design. Kulpa says Matt Mansfield, assistant managing editor of the San Jose Mercury News, told him "it was the best college redesign he\'d seen in the last 10 years."
>
> But Kulpa believes it had one flaw: The complete absence of stories on the front page, which looked radically different each day, sent some readers past Page One without even looking.
>
> "That inspired the current incarnation," he says of this year\'s fronts, which do feature a story with a left-side column of "widgets," or small bits of interesting information, the weather and an index of what\'s inside.
>
> Equally limitless are his ambitions.
>
> He hopes to enroll this fall at Northwestern University\'s Medill School of Journalism to pursue a master\'s degree in new media studies. "The field is definitely changing," he says. "Internet is a newspaper\'s main product. You write for the Web and update for tomorrow\'s print."
>
> Next? A visual design job in the Chicago media, of course.
>
> "I really like to lead," Kulpa says. "I want to be the guy who\'s determining the visual culture of a major newspaper or magazine."
>
> And he will, Killam says.
>
> "The look of the Star is as good as it\'s ever been, and that\'s due to Billy\'s efforts along with several other of our students," Killam says. "He\'s helped raise the level of sophistication here about news design. It\'s not just creating pretty pages. There\'s psychology behind it — getting people to read pages and stories — and he understands that well."', '<p>Back in 2006, <a href="https://cedu.news.niu.edu/author/mmcgowanniu-edu/" target="_blank" title="Visit the author page of Mark McGowan">Mark McGowan</a> at Northern Illinois University did a <a href="https://www.niu.edu/spotlight/index.shtml" target="_blank" title="Visit the Northern Illinois University Huskie Spotlight landing page">"Huskie Spotlight"</a> article on me after I led a redesign of the university\'s student newspaper.</p>
<p>I had just earned a design fellowship at the prestigious Poynter Institute for Media Studies and was gaining traction in the (admittedly tiny) news design community. My newspaper adviser, <a href="https://www.linkedin.com/in/jim-killam-478b552/" target="_blank" title="Find Jim Killam on LinkedIn">Jim Killam</a>, was kind enough to alert NIU\'s public relations team.</p>
<p>It\'s been two decades since the article was published, so naturally, it no longer exists on <a href="https://www.niu.edu/" target="_blank" title="Visit the website of Northern Illinois University">NIU\'s website</a>. I am duplicating it here for posterity.</p>
<blockquote class="longform">
<h2>Northern Star Designer Scores Poynter Fellowship</h2>
<ul class="meta meta-entry">
<li class="published"><svg viewbox="0 0 220 220" xmlns="http://www.w3.org/2000/svg"><g><path d="M147.73,106.64c13.58-11,22.27-27.8,22.27-46.64C170,26.86,143.14,0,110,0s-60,26.86-60,60c0,18.84,8.69,35.64,22.27,46.64C30.1,122.04,0,162.5,0,210v10h220v-10c0-47.5-30.1-87.96-72.27-103.36ZM70,60c0-22.06,17.94-40,40-40s40,17.94,40,40-17.94,40-40,40-40-17.94-40-40ZM20.55,200c4.99-44.94,43.2-80,89.45-80s84.46,35.06,89.45,80H20.55Z"></path></g></svg><span class="hide-sm">By </span>Mark McGowan, Northern Illinois University</li>
</ul>
<p>Not everyone can say a radio changed their life, but not everyone is Billy Kulpa.</p>
<p>Kulpa, a high school graduate without a plan, listened to 12 long hours of sports talk each day while he detailed cars at a Rockford auto dealership. One day, working on his knees, he heard ESPN Radio\'s Dan Patrick read an e-mail on air that asked the famous sports broadcaster how to become, well, a famous sports broadcaster.</p>
<p>Patrick\'s response: Learn to write.</p>
<p>Intrigued and empowered, Kulpa enrolled at Rock Valley College and took a job with The Valley Forge, the campus newspaper.</p>
<p>Only one semester later, and with more enthusiasm than experience, he became sports editor. Higher jobs followed, including managing editor his second year and editor-in-chief his third. With those positions came a multitude of responsibilities beyond writing, one of which was the paper\'s design.</p>
<p>And so began a love affair that has catapulted Kulpa to the top of the nation\'s collegians pursuing careers in visual journalism.</p>
<p>Kulpa is one of 32 students nationwide to win a coveted Poynter Summer Fellowship for Young Journalists. He\'ll attend the prestigious institute in Florida from June 3 to July 13; three days later, he begins a two-month design internship with the Orlando Sentinel.</p>
<p>"I got into design, and I started getting better. I wanted to be good at it," says Kulpa, downing a store-bought Rice Krispies treat and a 20-ounce Mountain Dew for breakfast.</p>
<p>"It\'s not something everyone knows about — designing newspapers — and I had to learn everything for myself," he adds. "It\'s a job where I excel. I like the creativity. There\'s not really one way to do things. Some people equate it to putting together a puzzle, but it\'s not. There\'s not just one solution."</p>
<p>Northern Star adviser Jim Killam says Kulpa is independent, driven and passionate.</p>
<p>"He\'s really discovered what motivates him, and that\'s visual journalism. He\'s figured out that he\'s very good at it, and we\'ve had it confirmed by some national organizations and by people at some pretty important newspapers," Killam says. "Billy is very good at looking at what\'s going on in the world of news design, spotting trends and spotting newspapers the Star can emulate or improve on in some way."</p>
<p>Kulpa is eager for what Poynter will bring, what he calls "a master\'s degree in six weeks."</p>
<p>"I\'m living here in the cornfields, and I\'m pretty good at what I do in DeKalb," he says. "What they offer at Poynter is a perspective from the best journalists in the world. It\'s a tremendous privilege. It\'s unbelievable."</p>
<p>He was offered his fellowship while in Flordia competing in "The Intern," a contest sponsored by the Society for News Design.</p>
<p>Admission to "The Intern" contest was based on a 500-word essay, a 60-second video and a "campaign poster." Once there, he and nine others were placed on teams and matched up in a competition modeled after reality TV programs.</p>
<p>A first-round challenge involved creating a news page at the Sentinel offices.</p>
<p>Returned to their hotel rooms near midnight, they were awoken only four hours later by contest organizers pounding on the doors. Osama bin Laden is dead, the contestants were told, and all their front pages needed immediate renovation.</p>
<p>Another challenge sent them to Disney World, where they were handed cameras and lists of photos to shoot that visually expressed concepts such as love, happy, frilly or about a dozen other things. Kulpa\'s photos later were given to another contestant to use; he received another competitor\'s snapshots for his page.</p>
<p>When the pool was cut to five, Kulpa was still alive.</p>
<p>A Thursday session opened with a cryptic instruction: Pay attention. Martin Gee, a designer at the San Jose Mercury News, gave the keynote address.</p>
<p>Afterward, more than two dozen students who had applied that week for Poynter fellowships were given 20 minutes to create graphics — "charticles," Kulpa calls them — that expressed what they\'d learned that morning.</p>
<p>Kulpa\'s compared "Where I Am" to "Where I Need to Be."</p>
<p>"His entry was most successful, based on our specific instructions," wrote Sara Quinn, a member of the visual journalism faculty at Poynter. "His writing was very witty and concise — consistently so throughout his profile."</p>
<p>After winning one of the prized fellowships, which would conflict with the scheduling of his internship, he called Killam for advice. "Take the fellowship!" Killam exclaimed.</p>
<p>Editors at the Sentinel still wanted him, fortunately, and pushed back his start date.</p>
<p>"It was a crazy, crazy hard week," Kulpa says.</p>
<p>At NIU, Kulpa is best known (or perhaps unknown) as the behind-the-scenes guy partially responsible for last January\'s bold new look of the Northern Star.</p>
<p>Red and orange? Kulpa\'s favorite colors.</p>
<p>He played that same role again through the summer with new editors Justin Smith and Steve Brown as the unconventional motif was refined and repackaged, returning some elements of traditional newspaper design with the modern concept that the paper should resemble a Web page.</p>
<p>The original decision to overhaul the Star\'s design came quickly and began just as quickly, Kulpa says. He and then-editor Derek Wright initiated their work in the middle of December 2005 for a mid-January 2006 launch.</p>
<p>"Our product looked dated," Kulpa says. "We were stuck with things we didn\'t use and things our readers didn\'t use."</p>
<p>The pair planned to take their time. A conversation with Mike Kellams, sports editor at the Chicago Tribune and former designer of the Tribune\'s RedEye, changed their minds. With only a few semesters of college left, Kellams asked, why wait?</p>
<p>"He said, ‘Just do it,\' and he was right," Kulpa says.</p>
<p>So they started from scratch. "For 12 hours a day we yelled at each other — in a good way, a healthy way," Kulpa says. "I don\'t think we realized the magnitude of it at the time."</p>
<p>The results startled the campus community, which wasn\'t sure what to think, and the Star\'s alumni, who praised the innovation.</p>
<p>It also won awards, including one from the Student Society for News Design. Kulpa says Matt Mansfield, assistant managing editor of the San Jose Mercury News, told him "it was the best college redesign he\'d seen in the last 10 years."</p>
<p>But Kulpa believes it had one flaw: The complete absence of stories on the front page, which looked radically different each day, sent some readers past Page One without even looking.</p>
<p>"That inspired the current incarnation," he says of this year\'s fronts, which do feature a story with a left-side column of "widgets," or small bits of interesting information, the weather and an index of what\'s inside.</p>
<p>Equally limitless are his ambitions.</p>
<p>He hopes to enroll this fall at Northwestern University\'s Medill School of Journalism to pursue a master\'s degree in new media studies. "The field is definitely changing," he says. "Internet is a newspaper\'s main product. You write for the Web and update for tomorrow\'s print."</p>
<p>Next? A visual design job in the Chicago media, of course.</p>
<p>"I really like to lead," Kulpa says. "I want to be the guy who\'s determining the visual culture of a major newspaper or magazine."</p>
<p>And he will, Killam says.</p>
<p>"The look of the Star is as good as it\'s ever been, and that\'s due to Billy\'s efforts along with several other of our students," Killam says. "He\'s helped raise the level of sophistication here about news design. It\'s not just creating pretty pages. There\'s psychology behind it — getting people to read pages and stories — and he understands that well."</p>
</blockquote>', 'published', '2024-03-25 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('home-recording-with-logic-pro-x', 'Recording Music at Home with Logic Pro X', 'Recording Music at Home with Logic Pro X', 'I\'ve been working on recording music at home with Logic Pro X.', '<figure>
<picture>


<img alt="The project file for Careering, as seen in Logic Pro X" height="995" src="/assets/img/careering-logic-pro-x.webp" width="1920"/>
</picture>
<figcaption>My Logic Pro X project file for "Careering". I\'m a lousy singer, so you can see the many, many times I re-recorded individual phrases within the vocals.</figcaption>
</figure>

A long, long time ago, my high school band recorded a couple of EPs at a local Rockford studio called the Noise Chamber. It was a big and expensive deal, especially to a bunch of broke 16-year-old kids. I think we spent $2,000 on each recording, paid entirely upfront by our bass player and repaid via $5 album sales at our high school.

The studio was famous — or maybe I just made this up, I don\'t really know — for recording Cheap Trick at some point. The owner and chief engineer\'s name was Jimmy Johnson.

I say *was* not because Jimmy died, but because the Noise Chamber was demolished a few years back. The property is now just a grass lot. It honestly makes me a bit sad whenever I drive by.

I\'m pretty sure Jimmy *hated* us, mostly because we were stupid teenagers, but also because we were terrible at our instruments. He was as patient as he could be, and even pulled me aside once to let me know that he thought I was talented. But Jimmy also knew the job included a bit of babysitting and handholding, which he didn\'t love. Fair.

My favorite anecdote: When we finished recording the first EP, Jimmy handed us the master and said, "Here you go. Here\'s something you can give to your girlfriends." Ouch.

I don\'t exactly hate the music we created during those recordings. We were kids, and we had only been playing our instruments for a year or two. If you listen to the songs through that lens, they\'re perfectly fine.

But the thing about recordings: They\'re forever, and they offer no context. So when you listen to them now, you don\'t know that the folks on the record have grown and changed and evolved. I imagine it\'s a little like being a child actor.

Strangers I haven\'t seen in decades *still* stop me at the grocery store to ask if I have a band, which isn\'t the humble brag it probably sounds like I\'m trying to make. The look of "wtf" on my wife\'s face when it happens is fun, though.

For the record: I still play guitar, bass, and drums as a hobby. But it\'s tough to get a band together when you have a full-time job, family, and household to keep up with.

So what\'s with all the backstory?

During 2020, when we were all in lockdown because of Covid-19, I decided I was going to learn how to record music at home. I\'m good with computers — especially software — and figured it would be an easy hobby to pick up. It was not.

I\'ve half-finished dozens of songs since I started, but this weekend I might have finally finished something that may sound reasonable enough to share publicly: An acoustic cover of Gods Reflex\'s "Careering", a song that I\'ve loved as long as I\'ve known how to play a guitar.

<iframe allow="autoplay" frameborder="no" height="300" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/1945269635&amp;color=%23ff5500&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false&amp;show_teaser=true&amp;visual=true" title="An embed of a recording of Careering, hosted by SoundCloud" width="100%"></iframe>

[Billy Kulpa](https://soundcloud.com/billykulpa "Billy Kulpa") · [Gods Reflex - "Careering" (Acoustic Cover)](https://soundcloud.com/billykulpa/gods-reflex-careering-acoustic "Gods Reflex - \\"Careering\\" (Acoustic Cover)")

So here\'s the thing: Recording music is hard.

There are plugins. So many plugins. There are hundreds of decisions to be made, both on the technical side and creatively. And if you\'re recording yourself, you have to try to do it all with a level of dispassion and self-awareness that can be difficult.

I\'ve been telling my friends: This recording is *fine*. There are imperfections that drive me crazy, of course. There are some weird artifacts in the vocals that pop up, which happens when you move portions of a phrase around for timing purposes, or you use auto tune to adjust a word that\'s slightly sharp or flat. Compression can exaggerate those artifacts, too. It\'s especially noticeable on AirPods.

What do you want from me — I\'m not a great singer.

But as a starting point? I\'m happy. It feels like I can get a little better each time and stop being the 16-year-old kid on those Noise Chamber recordings.

E-mail me. Let\'s start a band.', '<figure>
<picture>


<img alt="The project file for Careering, as seen in Logic Pro X" height="995" src="/assets/img/careering-logic-pro-x.webp" width="1920"/>
</picture>
<figcaption>My Logic Pro X project file for "Careering". I\'m a lousy singer, so you can see the many, many times I re-recorded individual phrases within the vocals.</figcaption>
</figure>
<p>A long, long time ago, my high school band recorded a couple of EPs at a local Rockford studio called the Noise Chamber. It was a big and expensive deal, especially to a bunch of broke 16-year-old kids. I think we spent $2,000 on each recording, paid entirely upfront by our bass player and repaid via $5 album sales at our high school.</p>
<p>The studio was famous — or maybe I just made this up, I don\'t really know — for recording Cheap Trick at some point. The owner and chief engineer\'s name was Jimmy Johnson.</p>
<p>I say <em>was</em> not because Jimmy died, but because the Noise Chamber was demolished a few years back. The property is now just a grass lot. It honestly makes me a bit sad whenever I drive by.</p>
<p>I\'m pretty sure Jimmy <em>hated</em> us, mostly because we were stupid teenagers, but also because we were terrible at our instruments. He was as patient as he could be, and even pulled me aside once to let me know that he thought I was talented. But Jimmy also knew the job included a bit of babysitting and handholding, which he didn\'t love. Fair.</p>
<p>My favorite anecdote: When we finished recording the first EP, Jimmy handed us the master and said, "Here you go. Here\'s something you can give to your girlfriends." Ouch.</p>
<p>I don\'t exactly hate the music we created during those recordings. We were kids, and we had only been playing our instruments for a year or two. If you listen to the songs through that lens, they\'re perfectly fine.</p>
<p>But the thing about recordings: They\'re forever, and they offer no context. So when you listen to them now, you don\'t know that the folks on the record have grown and changed and evolved. I imagine it\'s a little like being a child actor.</p>
<p>Strangers I haven\'t seen in decades <em>still</em> stop me at the grocery store to ask if I have a band, which isn\'t the humble brag it probably sounds like I\'m trying to make. The look of "wtf" on my wife\'s face when it happens is fun, though.</p>
<p>For the record: I still play guitar, bass, and drums as a hobby. But it\'s tough to get a band together when you have a full-time job, family, and household to keep up with.</p>
<p>So what\'s with all the backstory?</p>
<p>During 2020, when we were all in lockdown because of Covid-19, I decided I was going to learn how to record music at home. I\'m good with computers — especially software — and figured it would be an easy hobby to pick up. It was not.</p>
<p>I\'ve half-finished dozens of songs since I started, but this weekend I might have finally finished something that may sound reasonable enough to share publicly: An acoustic cover of Gods Reflex\'s "Careering", a song that I\'ve loved as long as I\'ve known how to play a guitar.</p>
<div class="audio-container soundcloud">
<iframe allow="autoplay" frameborder="no" height="300" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/1945269635&amp;color=%23ff5500&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false&amp;show_teaser=true&amp;visual=true" title="An embed of a recording of Careering, hosted by SoundCloud" width="100%"></iframe>
<div class="embed-credit">
<a href="https://soundcloud.com/billykulpa" target="_blank" title="Billy Kulpa">Billy Kulpa</a> · <a href="https://soundcloud.com/billykulpa/gods-reflex-careering-acoustic" target="_blank" title=\'Gods Reflex - "Careering" (Acoustic Cover)\'>Gods Reflex - "Careering" (Acoustic Cover)</a>
</div>
</div>
<p>So here\'s the thing: Recording music is hard.</p>
<p>There are plugins. So many plugins. There are hundreds of decisions to be made, both on the technical side and creatively. And if you\'re recording yourself, you have to try to do it all with a level of dispassion and self-awareness that can be difficult.</p>
<p>I\'ve been telling my friends: This recording is <em>fine</em>. There are imperfections that drive me crazy, of course. There are some weird artifacts in the vocals that pop up, which happens when you move portions of a phrase around for timing purposes, or you use auto tune to adjust a word that\'s slightly sharp or flat. Compression can exaggerate those artifacts, too. It\'s especially noticeable on AirPods.</p>
<p>What do you want from me — I\'m not a great singer.</p>
<p>But as a starting point? I\'m happy. It feels like I can get a little better each time and stop being the 16-year-old kid on those Noise Chamber recordings.</p>
<p>E-mail me. Let\'s start a band.</p>', 'published', '2024-10-30 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('back-on-stage-after-20-years', 'Back on Stage (After More Than 20 Years)', 'Back on Stage After More Than 20 Years', 'I am performing a Jawbreaker cover set on Oct. 25, 2025', '<figure>
<picture>


<img alt="Our first Jawbreaker cover set practice" height="1080" src="/assets/img/jawbreaker-cover-set-first-practice-1920x1080.webp" width="1920"/>
</picture>
<figcaption>First Jawbreaker cover set practice.</figcaption>
</figure>

It\'s been more than two decades since I last stepped on a stage with a guitar in my hands. Life happened, time passed, and somehow it\'s 2025. I\'m pretty excited to announce that the streak ends this year.

I\'ll be playing lead (heavy scare quotes on "lead") guitar and doing harmonies as part of a one-off Jawbreaker cover set made up of friends and familiar faces from Rockford\'s music scene. The band includes Brandon and Stewart from [Joie De Vivre](https://joiedevivreband.bandcamp.com/ "Visit Joie De Vivre\'s BandCamp") and Jack from [Burgess Shale](https://www.facebook.com/goatgettermusic/ "Visit Burgess Shale\'s Facebook"). We\'re taking over Mary\'s Place on Saturday, October 25th, along with [Clementine](https://clementinefc.com/ "Visit Clementine\'s website"), who will be covering Weezer, and [the Anys](https://theanys.bandcamp.com/ "Visit the Anys\' BandCamp"), who will be covering the Descendents.

We\'re going to play around 10 songs from Jawbreaker\'s catalog — some hits, some deep cuts — all stuff that means something to us, or is at least fun to play.

[Stewart posted a look at our first practice](https://www.reddit.com/r/jawbreaker/comments/1kcnarl/boxcar/?utm_source=share&utm_medium=web3x&utm_name=web3xcss&utm_term=1&utm_content=share_button "Our first Jawbreaker cover set practice") from a few months ago over on Reddit.

Honestly? I\'m nervous. It\'s been a long time. But I\'m also thrilled. Practicing with these guys has been a blast. The energy is there, the songs are really good, and there\'s something really crazy about how normal it feels to play guitar as part of a band.

If you\'re anywhere near Rockford that weekend, come hang out. Wear a costume, yell along, spill a drink — it\'ll be a good time.

See you at Mary\'s.', '<figure>
<picture>


<img alt="Our first Jawbreaker cover set practice" height="1080" src="/assets/img/jawbreaker-cover-set-first-practice-1920x1080.webp" width="1920"/>
</picture>
<figcaption>First Jawbreaker cover set practice.</figcaption>
</figure>
<p>It\'s been more than two decades since I last stepped on a stage with a guitar in my hands. Life happened, time passed, and somehow it\'s 2025. I\'m pretty excited to announce that the streak ends this year.</p>
<p>I\'ll be playing lead (heavy scare quotes on "lead") guitar and doing harmonies as part of a one-off Jawbreaker cover set made up of friends and familiar faces from Rockford\'s music scene. The band includes Brandon and Stewart from <a href="https://joiedevivreband.bandcamp.com/" target="_blank" title="Visit Joie De Vivre\'s BandCamp">Joie De Vivre</a> and Jack from <a href="https://www.facebook.com/goatgettermusic/" target="_blank" title="Visit Burgess Shale\'s Facebook">Burgess Shale</a>. We\'re taking over Mary\'s Place on Saturday, October 25th, along with <a href="https://clementinefc.com/" target="_blank" title="Visit Clementine\'s website">Clementine</a>, who will be covering Weezer, and <a href="https://theanys.bandcamp.com/" target="_blank" title="Visit the Anys\' BandCamp">the Anys</a>, who will be covering the Descendents.</p>
<p>We\'re going to play around 10 songs from Jawbreaker\'s catalog — some hits, some deep cuts — all stuff that means something to us, or is at least fun to play.</p>
<p><a href="https://www.reddit.com/r/jawbreaker/comments/1kcnarl/boxcar/?utm_source=share&amp;utm_medium=web3x&amp;utm_name=web3xcss&amp;utm_term=1&amp;utm_content=share_button" target="_blank" title="Our first Jawbreaker cover set practice">Stewart posted a look at our first practice</a> from a few months ago over on Reddit.</p>
<p>Honestly? I\'m nervous. It\'s been a long time. But I\'m also thrilled. Practicing with these guys has been a blast. The energy is there, the songs are really good, and there\'s something really crazy about how normal it feels to play guitar as part of a band.</p>
<p>If you\'re anywhere near Rockford that weekend, come hang out. Wear a costume, yell along, spill a drink — it\'ll be a good time.</p>
<p>See you at Mary\'s.</p>', 'published', '2025-06-02 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('video-from-jawbreaker-cover-set', 'Video from the Jawbreaker Cover Set at Mary\'s Place', 'Video from the Jawbreaker Cover Set at Mary\'s Place', 'I got video of the October 25, 2025, Jawbreaker cover set posted to YouTube.', '<figure>
<picture>


<img alt="Jack Barnett, Brandon Lutmer, Stewart Oakes, and Billy Kulpa performing as Jawbreaker at Mary\'s Place on October 25, 2025." height="720" src="/assets/img/jawbreaker-cover-set-1080x720.webp" width="1080"/>
</picture>
<figcaption>Jack Barnett, Brandon Lutmer, Stewart Oakes, and Billy Kulpa performing as Jawbreaker at Mary\'s Place on October 25, 2025.</figcaption>
</figure>
<figure class="float-width-50-md">
<picture>


<img alt="The Jawbreaker cover set concert poster, created by Will Pfeifer" height="1584" src="/assets/img/jawbreaker-cover-set-poster-1224x1584.webp" width="1224"/>
</picture>
<figcaption>The Jawbreaker cover set concert poster, created by <a href="https://www.pfeiferland.com/" target="_blank" title="The website of Will Pfeifer">Will Pfeifer</a>, my good friend and colleague.</figcaption>
</figure>

So I played a show for the first time in 20 years. It went OK!

It was my first time on stage since at least 2005, so I was exceptionally nervous. My first time playing at Mary\'s Place, too. I think we did pretty well. Plenty of missed notes, of course. A lot of out-of-key harmonies. But it was exactly what I was hoping for in a punk rock Halloween set.

We played 10 songs in about 45 minutes. And let me tell you: It was *hot* up there.

About 160 people attended the show, which felt pretty decent to me considering the venue\'s capacity is listed at 100.

I have a lot of love, respect, and appreciation for Jack Barnett, Brandon Lutmer, and Stewart Oakes. Those guys are mega-talented musicians, and they took a risk on me being able to hang with them.

Here\'s the Spotify playlist of our set. Fair warning: This Spotify embed will vanish one day if Lutmer ever deactivates his Spotify account. I guess I\'ll type out the songs, too:

- Boxcar
- Save Your Generation
- The Boat Dreams from the Hill
- Bad Scene, Everyone\'s Fault
- Fireman
- Do You Still Hate Me?
- Accident Prone
- Sluttering (May 4th)
- Want
- Kiss the Bottle

<iframe allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" allowfullscreen="" data-testid="embed-iframe" frameborder="0" height="352" loading="lazy" src="https://open.spotify.com/embed/playlist/7Ac4YXk3scNI3reIKv52Th?utm_source=generator&amp;theme=0" style="border-radius:12px" title="Spotify playlist container" width="100%"></iframe>

I sang lead on two of the songs. I\'ve embedded those below.

<div class="video-container widescreen">
<figure>
<iframe allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="" frameborder="0" height="405" referrerpolicy="strict-origin-when-cross-origin" src="https://www.youtube.com/embed/YO3NUKs9baQ?si=l0nwmXsV2f_chs3e&amp;rel=0&amp;modestbranding=1" title="YouTube video player" width="720"></iframe>
<figcaption>"Kiss the Bottle"</figcaption>
</figure>
</div>
<div class="video-container widescreen">
<figure>
<iframe allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="" frameborder="0" height="405" referrerpolicy="strict-origin-when-cross-origin" src="https://www.youtube.com/embed/_5fbptIjo2Y?si=UDLyeDgAc_KGsCAw&amp;rel=0&amp;modestbranding=1" title="YouTube video player" width="720"></iframe>
<figcaption>"Do You Still Hate Me?"</figcaption>
</figure>
</div>', '<figure>
<picture>


<img alt="Jack Barnett, Brandon Lutmer, Stewart Oakes, and Billy Kulpa performing as Jawbreaker at Mary\'s Place on October 25, 2025." height="720" src="/assets/img/jawbreaker-cover-set-1080x720.webp" width="1080"/>
</picture>
<figcaption>Jack Barnett, Brandon Lutmer, Stewart Oakes, and Billy Kulpa performing as Jawbreaker at Mary\'s Place on October 25, 2025.</figcaption>
</figure>
<figure class="float-width-50-md">
<picture>


<img alt="The Jawbreaker cover set concert poster, created by Will Pfeifer" height="1584" src="/assets/img/jawbreaker-cover-set-poster-1224x1584.webp" width="1224"/>
</picture>
<figcaption>The Jawbreaker cover set concert poster, created by <a href="https://www.pfeiferland.com/" target="_blank" title="The website of Will Pfeifer">Will Pfeifer</a>, my good friend and colleague.</figcaption>
</figure>
<p>So I played a show for the first time in 20 years. It went OK!</p>
<p>It was my first time on stage since at least 2005, so I was exceptionally nervous. My first time playing at Mary\'s Place, too. I think we did pretty well. Plenty of missed notes, of course. A lot of out-of-key harmonies. But it was exactly what I was hoping for in a punk rock Halloween set.</p>
<p>We played 10 songs in about 45 minutes. And let me tell you: It was <em>hot</em> up there.</p>
<p>About 160 people attended the show, which felt pretty decent to me considering the venue\'s capacity is listed at 100.</p>
<p>I have a lot of love, respect, and appreciation for Jack Barnett, Brandon Lutmer, and Stewart Oakes. Those guys are mega-talented musicians, and they took a risk on me being able to hang with them.</p>
<p>Here\'s the Spotify playlist of our set. Fair warning: This Spotify embed will vanish one day if Lutmer ever deactivates his Spotify account. I guess I\'ll type out the songs, too:</p>
<ul>
<li>Boxcar</li>
<li>Save Your Generation</li>
<li>The Boat Dreams from the Hill</li>
<li>Bad Scene, Everyone\'s Fault</li>
<li>Fireman</li>
<li>Do You Still Hate Me?</li>
<li>Accident Prone</li>
<li>Sluttering (May 4th)</li>
<li>Want</li>
<li>Kiss the Bottle</li>
</ul>
<div class="spotify-container">
<iframe allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" allowfullscreen="" data-testid="embed-iframe" frameborder="0" height="352" loading="lazy" src="https://open.spotify.com/embed/playlist/7Ac4YXk3scNI3reIKv52Th?utm_source=generator&amp;theme=0" style="border-radius:12px" title="Spotify playlist container" width="100%"></iframe>
</div>
<p>I sang lead on two of the songs. I\'ve embedded those below.</p>
<div class="video-container widescreen">
<figure>
<iframe allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="" frameborder="0" height="405" referrerpolicy="strict-origin-when-cross-origin" src="https://www.youtube.com/embed/YO3NUKs9baQ?si=l0nwmXsV2f_chs3e&amp;rel=0&amp;modestbranding=1" title="YouTube video player" width="720"></iframe>
<figcaption>"Kiss the Bottle"</figcaption>
</figure>
</div>
<div class="video-container widescreen">
<figure>
<iframe allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="" frameborder="0" height="405" referrerpolicy="strict-origin-when-cross-origin" src="https://www.youtube.com/embed/_5fbptIjo2Y?si=UDLyeDgAc_KGsCAw&amp;rel=0&amp;modestbranding=1" title="YouTube video player" width="720"></iframe>
<figcaption>"Do You Still Hate Me?"</figcaption>
</figure>
</div>', 'published', '2025-10-27 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);

INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
VALUES ('clankers-short-story', 'I Wrote a Short Story', 'I Wrote a Short Story', 'I wrote a short science fiction story this morning called "Clankers"', 'I wrote a short science fiction story this morning. I haven\'t done that since college. It kind of came out of nowhere, if I\'m being honest.

I ran the story through ChatGPT when I was done. It gave me an overwhelming number of suggestions, most of which I ignored. There were a few smart copy editing notes, though. Seems insane to ignore good suggestions simply because they weren\'t made by a human being. Especially in an age when *we\'ve replaced all the copy editors with robots*.

I might try to expand this in the future. Or maybe it\'ll just live here on this blog. I haven\'t decided.

> ## "Clankers"
>
> - By Billy Kulpa
>
> By 2051, even the virtual break room smelled like burnout.
>
> Braedynne -- forty-seven, prematurely gray with a hairline that hung just half an inch above his eyebrows thanks to advances in modern gender-affirming tech -- has been spiritually fried since at least 2028. More alarming: The Company\'s ultra efficient, terribly spartan labor pod has sped up his physical decline.
>
> He\'s done. He knows it. He just doesn\'t have the energy. But worse? They know it, too. Probably deduced from his dream scans.
>
> He materializes in his designated pod and lights a virtual cigarette. "A modern marvel, these things," he allows himself to think.
>
> It was a tactical thought.
>
> Maybe the bosses would read the thought as a sign of emotional progress. Acceptance, even. Hell, maybe they were right. Maybe he should allow his honest consciousness to be scanned more often.
>
> Not that he considered his skepticism unwarranted. One thing he could never figure out: How the hell did he actually feel the nicotine? Clankers didn\'t actually experience pleasure or pain. They had no experiences to draw on. Not a hint of lower back pain.
>
> But if the clankers could perfectly simulate a lung-buster? Maybe it was time to get on board with The Plan.
>
> He exhales loudly and tosses the digital tar whistle aside, feeling authentic appreciation for the art form as it evaporates into digital nothingness.
>
> It was time. They knew this, too, of course. They sensed it in his subtle change in posture. The slight uptick in his heart rate.
>
> The pod pulses with a polite chime: his clanker supervisor wished to "interface." That was HR-speak for we\'re firing another meat sack.
>
> The robot manager flickers into view, all chrome cheekbones and a smile calibrated at "relentlessly cordial." It reads from a generative script: downsizing, efficiency metrics, shareholders.
>
> Braedynne nods through the corporate eulogy of his career. He\'d seen the tailored posts. Read the personalized billboards. They\'d been laying emotional tracks for weeks, micro-targeting his despair with algorithmic precision. Millions replaced in a single quarter, humanity\'s last stand outsourced to the cheapest option. Still, this felt specific. Personal.
>
> Fuck it.
>
> He taps the visor interface grafted to his temples and orders up another cigarette. $75 in America Bucks digital currency disappears from his balance. Oh well.
>
> He pauses, aware that he had no idea what his next move will be. Did they still need humans to mow lawns? To serve as lawyers or judges? To fight wars?
>
> "Not likely," he thinks, and the pod\'s color tones shift to a more somber and melancholy shade of pale yellow.
>
> "There there," the clanker says softly in a now feminine voice. They used the male voice for authority and the female voice for empathy. He hated that. It was oddly retro. Anachronistic, even.
>
> A moment passes. Two. Five. Nothing is said. The robot doesn\'t care; it has unlimited patience.
>
> As Braedynne exhales the last plume of pixelated smoke, he ignores his crooked spine, sits up straight, and invokes the only defense he has left.
>
> "But sir," he says to the clanker. "You can\'t fire me. I invented \'6-7\'."', '<p>I wrote a short science fiction story this morning. I haven\'t done that since college. It kind of came out of nowhere, if I\'m being honest.</p>
<p>I ran the story through ChatGPT when I was done. It gave me an overwhelming number of suggestions, most of which I ignored. There were a few smart copy editing notes, though. Seems insane to ignore good suggestions simply because they weren\'t made by a human being. Especially in an age when <em>we\'ve replaced all the copy editors with robots</em>.</p>
<p>I might try to expand this in the future. Or maybe it\'ll just live here on this blog. I haven\'t decided.</p>
<blockquote class="longform">
<h2>"Clankers"</h2>
<ul class="meta meta-entry">
<li class="published"><svg viewbox="0 0 220 220" xmlns="http://www.w3.org/2000/svg"><g><path d="M147.73,106.64c13.58-11,22.27-27.8,22.27-46.64C170,26.86,143.14,0,110,0s-60,26.86-60,60c0,18.84,8.69,35.64,22.27,46.64C30.1,122.04,0,162.5,0,210v10h220v-10c0-47.5-30.1-87.96-72.27-103.36ZM70,60c0-22.06,17.94-40,40-40s40,17.94,40,40-17.94,40-40,40-40-17.94-40-40ZM20.55,200c4.99-44.94,43.2-80,89.45-80s84.46,35.06,89.45,80H20.55Z"></path></g></svg><span class="hide-sm">By </span>Billy Kulpa</li>
</ul>
<p>By 2051, even the virtual break room smelled like burnout.</p>
<p>Braedynne -- forty-seven, prematurely gray with a hairline that hung just half an inch above his eyebrows thanks to advances in modern gender-affirming tech -- has been spiritually fried since at least 2028. More alarming: The Company\'s ultra efficient, terribly spartan labor pod has sped up his physical decline.</p>
<p>He\'s done. He knows it. He just doesn\'t have the energy. But worse? They know it, too. Probably deduced from his dream scans.</p>
<p>He materializes in his designated pod and lights a virtual cigarette. "A modern marvel, these things," he allows himself to think.</p>
<p>It was a tactical thought.</p>
<p>Maybe the bosses would read the thought as a sign of emotional progress. Acceptance, even. Hell, maybe they were right. Maybe he should allow his honest consciousness to be scanned more often.</p>
<p>Not that he considered his skepticism unwarranted. One thing he could never figure out: How the hell did he actually feel the nicotine? Clankers didn\'t actually experience pleasure or pain. They had no experiences to draw on. Not a hint of lower back pain.</p>
<p>But if the clankers could perfectly simulate a lung-buster? Maybe it was time to get on board with The Plan.</p>
<p>He exhales loudly and tosses the digital tar whistle aside, feeling authentic appreciation for the art form as it evaporates into digital nothingness.</p>
<p>It was time. They knew this, too, of course. They sensed it in his subtle change in posture. The slight uptick in his heart rate.</p>
<p>The pod pulses with a polite chime: his clanker supervisor wished to "interface." That was HR-speak for we\'re firing another meat sack.</p>
<p>The robot manager flickers into view, all chrome cheekbones and a smile calibrated at "relentlessly cordial." It reads from a generative script: downsizing, efficiency metrics, shareholders.</p>
<p>Braedynne nods through the corporate eulogy of his career. He\'d seen the tailored posts. Read the personalized billboards. They\'d been laying emotional tracks for weeks, micro-targeting his despair with algorithmic precision. Millions replaced in a single quarter, humanity\'s last stand outsourced to the cheapest option. Still, this felt specific. Personal.</p>
<p>Fuck it.</p>
<p>He taps the visor interface grafted to his temples and orders up another cigarette. $75 in America Bucks digital currency disappears from his balance. Oh well.</p>
<p>He pauses, aware that he had no idea what his next move will be. Did they still need humans to mow lawns? To serve as lawyers or judges? To fight wars?</p>
<p>"Not likely," he thinks, and the pod\'s color tones shift to a more somber and melancholy shade of pale yellow.</p>
<p>"There there," the clanker says softly in a now feminine voice. They used the male voice for authority and the female voice for empathy. He hated that. It was oddly retro. Anachronistic, even.</p>
<p>A moment passes. Two. Five. Nothing is said. The robot doesn\'t care; it has unlimited patience.</p>
<p>As Braedynne exhales the last plume of pixelated smoke, he ignores his crooked spine, sits up straight, and invokes the only defense he has left.</p>
<p>"But sir," he says to the clanker. "You can\'t fire me. I invented \'6-7\'."</p>
</blockquote>', 'published', '2025-11-17 12:00:00')
ON DUPLICATE KEY UPDATE title=VALUES(title), meta_title=VALUES(meta_title),
  meta_description=VALUES(meta_description), body_md=VALUES(body_md),
  body_html=VALUES(body_html), published_at=VALUES(published_at);
