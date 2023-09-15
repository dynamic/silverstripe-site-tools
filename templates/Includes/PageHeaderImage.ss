<% if $PageHeaderImage %>
    <div class="header">
        <% with $PageHeaderImage %>
            <img src="$Image.URL" alt="$Image.Title" class="img-fluid">
        <% end_with %>

        <% with $PageHeaderImage %>
            <% if $Title || $Content || $Top.Content || $HeaderLink %>
                <div class="slide-text">
                    <div class="inner">
                        <h1 class="element__title"><% if Title %>$Title<% else %>$Top.Title<% end_if %></h1>
                        <% if $Content || $Top.Content %>
                            <div class="typography"><% if $Content %>$Content<% else_if $Top.Content %>$Top.Content<% end_if %></div>
                        <% else %>
                            <div class="typography missing">
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean euismod bibendum laoreet. Proin gravida dolor sit amet lacus accumsan et viverra justo commodo. Proin sodales pulvinar sic tempor. Sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nam fermentum, nulla luctus pharetra vulputate, felis tellus mollis orci, sed rhoncus pronin sapien nunc accuan eget.</p>
                            </div>
                        <% end_if %>
                        <% if $HeaderLink %><p class="mb-0"><a href="$HeaderLink.LinkURL" class="btn btn-primary" title="Go to the $HeaderLink.SiteTree.MenuTitle.XML page"><% if $HeaderLink.Title %>$HeaderLink.Title<% else %>Learn More<% end_if %></a></p><% end_if %>
                    </div>
                </div>
            <% end_if %>
        <% end_with %>
        <div class="header__overlay"></div>
    </div>
<% else_if $SlideShow %>
    <div class="flexslider">
        <ul class="slides" role="flexslider">
            <% loop $SlideShow %>
                <% if $Image %>
                <li role="slide">
                    <img src="$Image.URL" alt="$Image.Title.ATT">
                    <% if $Headline || $Description || $SlideLink %>
                        <div class="overlay"> </div>
                        <div class="slide-text">
                            <div class="container">
                                <div class="row">
                                    <div class="col-md-12">
                                        <% if $Headline %><h1>$Headline</h1><% end_if %>
                                        <% if $Description %><p>$Description</p><% end_if %>
                                        <% if $HeaderLink %><p class="mb-0"><a href="$HeaderLink.LinkURL" class="btn btn-primary" title="Go to the $HeaderLink.SiteTree.MenuTitle.XML page">Learn more</a></p><% end_if %>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <% end_if %>
                </li>
                <% end_if %>
            <% end_loop %>
        </ul>
    </div>
<% end_if %>
