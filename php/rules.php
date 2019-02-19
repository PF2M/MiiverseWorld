<?php
$title = 'Rules';
require_once('inc/header.php');
if(!empty($_SESSION['username'])) {
    $row = initUser($_SESSION['username'], true);
} else {
    $is_general = true;
    require_once('elements/user-sidebar.php');
}
?>
<div class="main-column post-list-outline" id="help">
    <h2 class="label">Site Rules</h2>
    <div id="guide" class="help-content">
        <div class="num1">
            <h2>Miiverse World Rules</h2>
            <p>To help keep the site in order, there are some rules we'd like you to follow. This site is much more open-ended than other Miiverse clones, and as such the rules are mostly set by the communities you visit, but there are still some ground rules so anarchy doesn't break out. Please read this short list before using the site.</p>
            <h3>Follow Community Rules</h3>
            <p>As mentioned above, the rules on this page must be followed <em>alongside</em> the rules of the communities you use. As long as the community's rules don't override site-wide rules, the staff of the community can set whatever rules they wish, and are freely allowed to delete your community posts/replies or set their content as sensitive. The only responsibility community admins don't have is the ability to do site-wide actions like deleting posts made outside the community or give out global bans; this power is reserved solely for Miiverse World staff members. Posts and replies made to communities have two separate report options, one for breaking community rules and one for breaking site-wide rules. The latter is sent to Miiverse World's site staff, whose decisions preside over those of community admins. It is the responsibility of the userbase to use these powers properly.</p>
            <p>It should go without saying, but none of this applies to posts made outside of communities.</p>
            <h3>Sensitive Content and Tagging</h3>
            <p>While most clones forbid NSFW/NSFL content outside of the occasional dedicated community, Miiverse World prides itself in being free and open in that you can post NSFW content wherever you like, as long as you follow five simple rules:</p>
            <ol>
                <li>All NSFW content must be tagged using the Sensitive Content option provided on every post form. This option is also required for those posting spoilers or other content that the general userbase might not want to see.</li>
                <li>NSFL content, like gore or shock images, must additionally provide some kind of warning before the user is exposed to the image. Three examples of proper warnings include:
                    <ol>
                        <li>Posting the image in the comments of a post, also with Sensitive Content enabled, and saying something like "[image name] in the comments" in the main post body or the comments above the image.</li>
                        <li>Posting the image in a community with a name warning about the content, like "[NSFL] NES Open Tournament Community".</li>
                        <li>Using an account specifically named to warn about the content, like "Cursed NSFL Images".</li>
                    </ol>
                </li>
                <li>NSFW/NSFL content is not allowed in places that can't be marked as sensitive, like avatars, favorite posts and community icons.</li>
                <li>Illegal content, such as child pornography or privacy-invading photos, is not allowed anywhere on the site. The police will be contacted if necessary.</li>
                <li>Community admins have the right to disallow any kind of NSFW content within their community. Once again, this doesn't apply to posts made outside communities. Unsolicited NSFW/NSFL content in comments or messages is also not allowed.</li>
            </ol>
            <p>Besides NSFW/NSFL content, there's also another rule that needs to be followed regarding post metadata. Tagging is a feature introduced in Miiverse World that you may have already heard about, and as you might expect, using tags improperly is not allowed on the site. For instance, posting images of Kirby with tags that have nothing to do with the post like "mario, xbox, undertale" to gain more Yeahs is against the rules, and your tags may be removed or your post deleted if reported to the admins. Jokingly unrelated tags are something of a gray line, and can be subjected to further investigation by the admins. You should generally be fine as long as you're not entering the realm of clickbait, though.</p>
            <h3>Common Decency</h3>
            <p>Here at Miiverse World, we try to be polite and want users of all kinds to be comfortable on our site. To keep this standard up, we ask that you follow a few common decency rules:</p>
            <ul>
                <li>Harassment, hate speech, and raiding are not allowed anywhere on Miiverse World, and action will be taken accordingly. What constitutes hate speech will be decided by the admins.</li>
                <li>Hacking and/or doxxing is also against the rules, and will be punished with a ban if necessary.</li>
                <li>Spam is generally disallowed, unless it's deemed by the staff to be for a valid reason.</li>
            </ul>
            <h3>Other Rules</h3>
            <p>Lastly, here are a few extra rules:</p>
            <ul>
                <li>Miiverse World staff are allowed to have the last say about anything on the site. Community admins are one level below them, and regular users one level below that.</li>
                <li>Any attempts to find loopholes within the rules will be met with the according action, and the Miiverse World staff reserves the right to change the rules at any time.</li>
            </ul>
            <p>That should be about all the rules. Only 5 in total, albeit rather verbose ones. Next, we'll cover some things that aren't rules, but rather general information you may want to know.</p>
        </div>
        <div class="num2">
            <h2>General Site Information</h2>
            <h3>About Miiverse World</h3>
            <p>Miiverse World is a Miiverse clone experience that was developed by PF2M in PHP from late 2018 to early 2019. The site was created from a desire to innovate from the usual community posting structure of other Miiverse clones, taking ideas from other services like Twitter and Reddit. It features new additions to the Miiverse clone structure, like replacing the idea of a general discussion community with a post feed easily customizable by the user.  is also accomplished all without editing any of the site's client-side code or styling, using only HTML from the server to do everything. Because of this, you may find it a bit more limited than other clones, but this also makes it faster and more extensible as any themes or mods compatible with Miiverse are also compatible with Miiverse World. <a href="https://github.com/PF2M/MiiverseWorld">The source code can be found on GitHub, and edits/rehosts are allowed and encouraged.</a></p>
            <h3>Legal Information</h3>
            <p>Miiverse World is not, in any way, associated with Nintendo Co, Ltd. or Hatena Co, Ltd. Nintendo and Hatena have no involvement with this service, and neither company maintains, endorses, sponsors or contributes to this. <a href="https://pf2m.com/contact/">If anything found on this site infringes on your rights, contact us and we can fix it.</a></p>
        </div>
    </div>
</div>
<?php
require_once('inc/footer.php');
?>