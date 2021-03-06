<?php
// src/settings/s_reviews.php -- HotCRP settings > reviews page
// HotCRP is Copyright (c) 2006-2016 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

class SettingRenderer_Reviews extends SettingRenderer {
    private function echo_round($sv, $rnum, $nameval, $review_count, $deletable) {
        global $Conf;
        $rname = "roundname_$rnum";
        if ($sv->use_req() && $rnum !== '$')
            $nameval = (string) get($sv->req, $rname);
        $rname_si = $sv->si($rname);
        if ($nameval === "(new round)" || $rnum === '$')
            $rname_si->placeholder = "(new round)";
        $sv->set_oldv($rname, $nameval);

        echo '<div class="mg" data-round-number="', $rnum, '"><div>',
            $sv->label($rname, "Round"), ' &nbsp;',
            $sv->render_entry($rname);
        echo '<div class="inb" style="min-width:7em;margin-left:2em">';
        if ($rnum !== '$' && $review_count)
            echo '<a href="', hoturl("search", "q=" . urlencode("round:" . ($rnum ? $Conf->round_name($rnum, false) : "none"))), '">(', plural($review_count, "review"), ')</a>';
        echo '</div>';
        if ($deletable)
            echo '<div class="inb" style="padding-left:2em">',
                Ht::hidden("deleteround_$rnum", ""),
                Ht::js_button("Delete round", "review_round_settings.kill(this)"),
                '</div>';
        if ($rnum === '$')
            echo '<div class="hint">Names like “R1” and “R2” work well.</div>';
        echo '</div>';

        // deadlines
        $entrysuf = $rnum ? "_$rnum" : "";
        if ($rnum === '$' && count($Conf->round_list()))
            $dlsuf = "_" . (count($Conf->round_list()) - 1);
        else if ($rnum !== '$' && $rnum)
            $dlsuf = "_" . $rnum;
        else
            $dlsuf = "";
        $si = $sv->si("extrev_soft$dlsuf");
        $si->date_backup = "pcrev_soft$dlsuf";
        $si = $sv->si("extrev_hard$dlsuf");
        $si->date_backup = "pcrev_hard$dlsuf";

        echo '<table style="margin-left:3em">';
        echo '<tr><td>', $sv->label("pcrev_soft$entrysuf", "PC deadline"), ' &nbsp;</td>',
            '<td class="lentry" style="padding-right:3em">',
            $sv->render_entry("pcrev_soft$entrysuf"),
            '</td><td class="lentry">', $sv->label("pcrev_hard$entrysuf", "Hard deadline"), ' &nbsp;</td><td>',
            $sv->render_entry("pcrev_hard$entrysuf"),
            '</td></tr>';
        echo '<tr><td>', $sv->label("extrev_soft$entrysuf", "External deadline"), ' &nbsp;</td>',
            '<td class="lentry" style="padding-right:3em">',
            $sv->render_entry("extrev_soft$entrysuf"),
            '</td><td class="lentry">', $sv->label("extrev_hard$entrysuf", "Hard deadline"), ' &nbsp;</td><td>',
            $sv->render_entry("extrev_hard$entrysuf"),
            '</td></tr>';
        echo '</table></div>', "\n";
    }

function render(SettingValues $sv) {
    global $Conf;

    $sv->echo_checkbox("rev_open", "<b>Open site for reviewing</b>");
    $sv->echo_checkbox("cmt_always", "Allow comments even if reviewing is closed");

    echo "<div class='g'></div>\n";
    $sv->echo_checkbox('pcrev_any', "PC members can review <strong>any</strong> submitted paper");

    echo "<div class='g'></div>\n";
    echo "<strong>Review anonymity:</strong> Are reviewer names hidden from authors?<br />\n";
    $sv->echo_radio_table("rev_blind", array(Conf::BLIND_ALWAYS => "Yes—reviews are anonymous",
                               Conf::BLIND_NEVER => "No—reviewer names are visible to authors",
                               Conf::BLIND_OPTIONAL => "Depends—reviewers decide whether to expose their names"));

    echo "<div class='g'></div>\n";
    $sv->echo_checkbox('rev_notifychair', 'Notify PC chairs of newly submitted reviews by email');


    // Deadlines
    echo "<h3 id=\"rounds\" class=\"settings g\">Deadlines &amp; rounds</h3>\n";
    echo '<p class="hint">Reviews are due by the deadline, but <em>cannot be modified</em> after the hard deadline. Most conferences don’t use hard deadlines for reviews.<br />', ($sv->type_hint("date") ? : ""), '</p>';

    $rounds = $Conf->round_list();
    if ($sv->use_req()) {
        for ($i = 1; isset($sv->req["roundname_$i"]); ++$i)
            $rounds[$i] = get($sv->req, "deleteround_$i") ? ";" : trim(get_s($sv->req, "roundname_$i"));
    }

    // prepare round selector
    $round_value = trim($sv->curv("rev_roundtag"));
    $current_round_value = $Conf->setting_data("rev_roundtag", "");
    if (preg_match('/\A(?:|\(none\)|\(no name\)|default|unnamed|#0)\z/i', $round_value))
        $round_value = "#0";
    else if (($round_number = $Conf->round_number($round_value, false))
             || ($round_number = $Conf->round_number($current_round_value, false)))
        $round_value = "#" . $round_number;
    else
        $round_value = $selector[$current_round_value] = $current_round_value;

    $round_map = edb_map(Dbl::ql("select reviewRound, count(*) from PaperReview group by reviewRound"));

    $print_round0 = true;
    if ($round_value !== "#0" && $round_value !== ""
        && $current_round_value !== ""
        && (!$sv->use_req() || isset($sv->req["roundname_0"]))
        && !$Conf->round0_defined())
        $print_round0 = false;

    $selector = array();
    if ($print_round0)
        $selector["#0"] = "unnamed";
    for ($i = 1; $i < count($rounds); ++$i)
        if ($rounds[$i] !== ";")
            $selector["#$i"] = (object) array("label" => $rounds[$i], "id" => "rev_roundtag_$i");

    echo '<div id="round_container"', (count($selector) == 1 ? ' style="display:none"' : ''), '>',
        '<table id="rev_roundtag_table"><tr><td class="lxcaption">',
        $sv->label("rev_roundtag", "Current round"),
        '</td><td>',
        Ht::select("rev_roundtag", $selector, $round_value, $sv->sjs("rev_roundtag")),
        '</td></tr></table>',
        '<div class="hint">This round is used for new assignments.</div><div class="g"></div></div>';

    echo '<div id="roundtable">';
    $num_printed = 0;
    for ($i = 0; $i < count($rounds); ++$i)
        if ($i ? $rounds[$i] !== ";" : $print_round0) {
            $this->echo_round($sv, $i, $i ? $rounds[$i] : "", +get($round_map, $i), count($selector) !== 1);
            ++$num_printed;
        }
    echo '</div><div id="newround" style="display:none">';
    $this->echo_round($sv, '$', "", "", true);
    echo '</div><div class="g"></div>';
    echo Ht::js_button("Add round", "review_round_settings.add();hiliter(this)"),
        ' &nbsp; <span class="hint"><a href="', hoturl("help", "t=revround"), '">What is this?</a></span>',
        Ht::hidden("oldroundcount", count($Conf->round_list())),
        Ht::hidden("has_rev_roundtag", 1);
    for ($i = 1; $i < count($rounds); ++$i)
        if ($rounds[$i] === ";")
            echo Ht::hidden("roundname_$i", "", array("id" => "roundname_$i")),
                Ht::hidden("deleteround_$i", 1);
    Ht::stash_script("review_round_settings.init()");


    // External reviews
    echo "<h3 class=\"settings g\">External reviews</h3>\n";

    echo "<div class='g'></div>";
    $sv->echo_checkbox("extrev_chairreq", "PC chair must approve proposed external reviewers");
    $sv->echo_checkbox("pcrev_editdelegate", "PC members can edit external reviews they requested");

    echo "<div class='g'></div>\n";
    $t = expandMailTemplate("requestreview", false);
    echo "<table id='foldmailbody_requestreview' class='",
        ($t == expandMailTemplate("requestreview", true) ? "foldc" : "foldo"),
        "'><tr><td>", foldbutton("mailbody_requestreview"), "</td>",
        "<td><a href='#' onclick='return fold(\"mailbody_requestreview\")' class='q'><strong>Mail template for external review requests</strong></a>",
        " <span class='fx'>(<a href='", hoturl("mail"), "'>keywords</a> allowed; set to empty for default)<br /></span>
<textarea class='tt fx' name='mailbody_requestreview' cols='80' rows='20'>", htmlspecialchars($t["body"]), "</textarea>",
        "</td></tr></table>\n";


    // Review visibility
    echo "<h3 class=\"settings g\">Visibility</h3>\n";

    echo "Can PC members <strong>see all reviews</strong> except for conflicts?<br />\n";
    $sv->echo_radio_table("pc_seeallrev", array(Conf::PCSEEREV_YES => "Yes",
                                  Conf::PCSEEREV_UNLESSINCOMPLETE => "Yes, unless they haven’t completed an assigned review for the same paper",
                                  Conf::PCSEEREV_UNLESSANYINCOMPLETE => "Yes, after completing all their assigned reviews",
                                  Conf::PCSEEREV_IFCOMPLETE => "Only after completing a review for the same paper"));

    echo "<div class='g'></div>\n";
    echo "Can PC members see <strong>reviewer names</strong> except for conflicts?<br />\n";
    $sv->echo_radio_table("pc_seeblindrev", array(0 => "Yes",
                                    1 => "Only after completing a review for the same paper<br /><span class='hint'>This setting also hides reviewer-only comments from PC members who have not completed a review for the same paper.</span>"));

    echo "<div class='g'></div>";
    echo "Can external reviewers see the other reviews for their assigned papers, once they’ve submitted their own?<br />\n";
    $sv->echo_radio_table("extrev_view", array(2 => "Yes", 1 => "Yes, but they can’t see who wrote blind reviews", 0 => "No"));


    // Review ratings
    echo "<h3 class=\"settings g\">Review ratings</h3>\n";

    echo "Should HotCRP collect ratings of reviews? &nbsp; <a class='hint' href='", hoturl("help", "t=revrate"), "'>(Learn more)</a><br />\n";
    $sv->echo_radio_table("rev_ratings", array(REV_RATINGS_PC => "Yes, PC members can rate reviews", REV_RATINGS_PC_EXTERNAL => "Yes, PC members and external reviewers can rate reviews", REV_RATINGS_NONE => "No"));
}

    function crosscheck(SettingValues $sv) {
        global $Conf, $Now;
        $errored = false;
        foreach ($Conf->round_list() as $i => $rname) {
            $suffix = $i ? "_$i" : "";
            foreach (Conf::$review_deadlines as $deadline)
                if ($sv->has_interest($deadline . $suffix)
                    && $sv->newv($deadline . $suffix) > $Now
                    && $sv->newv("rev_open") <= 0
                    && !$errored) {
                    $sv->set_warning("rev_open", "A review deadline is set in the future, but the site is not open for reviewing. This is sometimes unintentional.");
                    $errored = true;
                    break;
                }
        }

        if (($sv->has_interest("au_seerev") || $sv->has_interest("pcrev_soft"))
            && $sv->newv("au_seerev") != Conf::AUSEEREV_NO
            && $sv->newv("au_seerev") != Conf::AUSEEREV_TAGS
            && $sv->newv("pcrev_soft") > 0
            && $Now < $sv->newv("pcrev_soft")
            && !$sv->has_errors())
            $sv->set_warning(null, "Authors can see reviews and comments although it is before the review deadline. This is sometimes unintentional.");
    }
}


class Round_SettingParser extends SettingParser {
    private $rev_round_changes = array();

    function parse(SettingValues $sv, Si $si) {
        global $Conf;
        if (!isset($sv->req["rev_roundtag"])) {
            $sv->save("rev_roundtag", null);
            return false;
        }
        // round names
        $roundnames = $roundnames_set = array();
        $roundname0 = $round_deleted = null;
        for ($i = 0;
             isset($sv->req["roundname_$i"]) || isset($sv->req["deleteround_$i"]) || !$i;
             ++$i) {
            $rname = trim(get_s($sv->req, "roundname_$i"));
            if ($rname === "(no name)" || $rname === "default" || $rname === "unnamed")
                $rname = "";
            if ((get($sv->req, "deleteround_$i") || $rname === "") && $i) {
                $roundnames[] = ";";
                if (Dbl::fetch_ivalue("select reviewId from PaperReview where reviewRound=$i limit 1"))
                    $this->rev_round_changes[] = array($i, 0);
                if ($round_deleted === null && !isset($sv->req["roundname_0"])
                    && $i < $sv->req["oldroundcount"])
                    $round_deleted = $i;
            } else if ($rname === "")
                /* ignore */;
            else if (($rerror = Conf::round_name_error($rname)))
                $sv->set_error("roundname_$i", $rerror);
            else if ($i == 0)
                $roundname0 = $rname;
            else if (get($roundnames_set, strtolower($rname))) {
                $roundnames[] = ";";
                $this->rev_round_changes[] = array($i, $roundnames_set[strtolower($rname)]);
            } else {
                $roundnames[] = $rname;
                $roundnames_set[strtolower($rname)] = $i;
            }
        }
        if ($roundname0 && !get($roundnames_set, strtolower($roundname0))) {
            $roundnames[] = $roundname0;
            $roundnames_set[strtolower($roundname0)] = count($roundnames);
        }
        if ($roundname0)
            array_unshift($this->rev_round_changes, array(0, $roundnames_set[strtolower($roundname0)]));

        // round deadlines
        foreach ($Conf->round_list() as $i => $rname) {
            $suffix = $i ? "_$i" : "";
            foreach (Conf::$review_deadlines as $k)
                $sv->save($k . $suffix, null);
        }
        $rtransform = array();
        if ($roundname0 && ($ri = $roundnames_set[strtolower($roundname0)])
            && !isset($sv->req["pcrev_soft_$ri"])) {
            $rtransform[0] = "_$ri";
            $rtransform[$ri] = false;
        }
        if ($round_deleted) {
            $rtransform[$round_deleted] = "";
            if (!isset($rtransform[0]))
                $rtransform[0] = false;
        }
        for ($i = 0; $i < count($roundnames) + 1; ++$i)
            if ((isset($rtransform[$i])
                 || ($i ? $roundnames[$i - 1] !== ";" : !isset($sv->req["deleteround_0"])))
                && get($rtransform, $i) !== false) {
                $isuffix = $i ? "_$i" : "";
                if (($osuffix = get($rtransform, $i)) === null)
                    $osuffix = $isuffix;
                $ndeadlines = 0;
                foreach (Conf::$review_deadlines as $k) {
                    $v = parse_value($sv, Si::get($k . $isuffix));
                    $sv->save($k . $osuffix, $v <= 0 ? null : $v);
                    $ndeadlines += $v > 0;
                }
                if ($ndeadlines == 0 && $osuffix)
                    $sv->save("pcrev_soft$osuffix", 0);
                foreach (array("pcrev_", "extrev_") as $k) {
                    list($soft, $hard) = ["{$k}soft$osuffix", "{$k}hard$osuffix"];
                    list($softv, $hardv) = [$sv->savedv($soft), $sv->savedv($hard)];
                    if (!$softv && $hardv)
                        $sv->save($soft, $hardv);
                    else if ($hardv && $softv > $hardv) {
                        $desc = $i ? ", round " . htmlspecialchars($roundnames[$i - 1]) : "";
                        $sv->set_error($soft, Si::get("{$k}soft", "short_description") . $desc . ": Must come before " . Si::get("{$k}hard", "short_description") . ".");
                        $sv->set_error($hard);
                    }
                }
            }

        // round list (save after deadlines processing)
        while (count($roundnames) && $roundnames[count($roundnames) - 1] === ";")
            array_pop($roundnames);
        $sv->save("tag_rounds", join(" ", $roundnames));

        // default round
        $t = trim($sv->req["rev_roundtag"]);
        $sv->save("rev_roundtag", null);
        if (preg_match('/\A(?:|\(none\)|\(no name\)|default|unnamed)\z/i', $t))
            /* do nothing */;
        else if ($t === "#0") {
            if ($roundname0)
                $sv->save("rev_roundtag", $roundname0);
        } else if (preg_match('/^#[1-9][0-9]*$/', $t)) {
            $rname = get($roundnames, substr($t, 1) - 1);
            if ($rname && $rname !== ";")
                $sv->save("rev_roundtag", $rname);
        } else if (!($rerror = Conf::round_name_error($t)))
            $sv->save("rev_roundtag", $t);
        else
            $sv->set_error("rev_roundtag", $rerror);
        if (count($this->rev_round_changes)) {
            $sv->need_lock["PaperReview"] = true;
            return true;
        } else
            return false;
    }
    public function save(SettingValues $sv, Si $si) {
        global $Conf;
        // remove references to deleted rounds
        foreach ($this->rev_round_changes as $x)
            $Conf->qe("update PaperReview set reviewRound=$x[1] where reviewRound=$x[0]");
    }
}


SettingGroup::register("reviews", "Reviews", 500, new SettingRenderer_Reviews);
SettingGroup::register_synonym("rev", "reviews");
SettingGroup::register_synonym("review", "reviews");
