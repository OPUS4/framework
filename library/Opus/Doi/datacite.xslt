<?xml version="1.0" encoding="utf-8"?>
<!--
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */
-->

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:php="http://php.net/xsl"
                xmlns="http://datacite.org/schema/kernel-4">

    <xsl:output method="xml" indent="yes" encoding="utf-8"/>
    <!--
    Suppress output for all elements that don't have an explicit template.
    -->
    <xsl:template match="*"/>

    <xsl:template match="Opus">
        <xsl:apply-templates select="Opus_Document"/>
    </xsl:template>

    <xsl:template match="Opus_Document">
        <xsl:element name="resource">
            <xsl:attribute name="xsi:schemaLocation">http://datacite.org/schema/kernel-4 http://schema.datacite.org/meta/kernel-4/metadata.xsd</xsl:attribute>

            <!-- die bei DataCite zu registrierende DOI (muss eine lokale DOI sein) -->
            <xsl:apply-templates select="Identifier[@Type='doi']"/>

            <!-- Pflichtangabe: Element creators mit Kindelement creator mit Kindelement creatorName -->
            <!-- Gibt es weder Autoren noch eine urhebende Koerperschaft, wird der Herausgeber,
                ansonsten der Platzhalter "(:unav)" (unavailable, possibly unknown) ausgegeben -->
            <xsl:element name="creators">
                <xsl:choose>
                    <xsl:when test="PersonAuthor or @CreatingCorporation">
                        <xsl:apply-templates select="PersonAuthor"/>
                        <xsl:apply-templates select="@CreatingCorporation"/>
                    </xsl:when>
                    <xsl:when test="PersonEditor">
                        <xsl:apply-templates select="PersonEditor" mode="creator"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:element name="creator">
                            <xsl:element name="creatorName">
                                <xsl:text>(:unav)</xsl:text>
                            </xsl:element>
                        </xsl:element>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:element>

            <!-- Pflichtangabe: Element titles mit Kindelement title -->
            <!-- Ist kein Titel vorhanden, wird der Platzhalter "(:unas)" (unassigned) ausgegeben -->
            <xsl:element name="titles">
                <xsl:choose>
                    <xsl:when test="TitleMain or TitleSub">
                        <xsl:apply-templates select="TitleMain"/>
                        <xsl:apply-templates select="TitleSub"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:element name="title">
                            <xsl:text>(:unas)</xsl:text>
                        </xsl:element>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:element>

            <!-- Pflichtangabe: Auswertungsreihenfolge mit BSZ abgesprochen -->
            <!-- es kann pro Dokument mehr als einen ThesisPublisher geben: die Reihenfolge der ThesisPublisher
                 im OPUS-XML entspricht nicht der Reihenfolge in der Administration (im XML werden die TPs nach
                 ihrem Schlüsselwert sortiert ausgeben
                 da das Element publisher in DataCite-XML nicht wiederholbar ist, kann nur ein TP ausgewählt werden
                 aufgrund der oben beschriebenen Tatsache ist das immer der TP mit dem kleinsten Schlüsselwert, der
                 dem aktuellen Dokument zugeordnet ist -->
            <!-- Gibt es weder einen Verlag noch einen ThesisPublisher, werden Koerperschaften,
                    ansonsten der Platzhalter "(:unav)" (unavailable, possibly unknown) ausgegeben -->
            <xsl:element name="publisher">
                <xsl:choose>
                    <xsl:when test="@PublisherName != ''">
                        <xsl:value-of select="@PublisherName"/>
                    </xsl:when>
                    <xsl:when test="ThesisPublisher/@Name != ''">
                        <xsl:value-of select="ThesisPublisher/@Name"/>
                    </xsl:when>
                    <xsl:when test="@CreatingCorporation">
                        <xsl:value-of select="@CreatingCorporation"/>
                    </xsl:when>
                    <xsl:when test="@ContributingCorporation">
                        <xsl:value-of select="@ContributingCorporation"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text>(:unav)</xsl:text>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:element>

            <!-- Pflichtangabe -->
            <xsl:apply-templates select="ServerDatePublished"/>

            <!-- Pflichtangabe -->
            <xsl:apply-templates select="@Type"/>

            <!-- nachfolgend kommen die optionalen Elemente -->

            <xsl:if test="Collection[@RoleName='ddc' and @Visible=1]">
                <xsl:element name="subjects">
                    <xsl:apply-templates select="Collection[@RoleName='ddc' and @Visible=1]" />
                </xsl:element>
            </xsl:if>

            <!-- PersonEditor nur dann ausgeben, wenn nicht bereits im Element creator ausgegeben:
                 das ist nicht der Fall, wenn PersonAuthor oder CreatingCorporation existiert -->
            <xsl:if test="PersonEditor and (PersonAuthor or @CreatingCorporation)">
                <xsl:element name="contributors">
                    <xsl:apply-templates select="PersonEditor"/>
                </xsl:element>
            </xsl:if>

            <xsl:apply-templates select="ServerDateCreated"/>

            <xsl:apply-templates select="@Language"/>

            <xsl:if test="Identifier[@Type='isbn'] or Identifier[@Type='urn']">
                <xsl:element name="alternateIdentifiers">
                    <xsl:apply-templates select="Identifier[@Type='urn']"/>
                    <xsl:apply-templates select="Identifier[@Type='isbn']"/>
                </xsl:element>
            </xsl:if>

            <xsl:if test="Identifier[@Type='issn']">
                <xsl:element name="relatedIdentifiers">
                    <xsl:apply-templates select="Identifier[@Type='issn']"/>
                </xsl:element>
            </xsl:if>

            <xsl:if test="File/@FileSize or @PageNumber">
                <xsl:element name="sizes">
                    <xsl:apply-templates select="File/@FileSize"/>
                    <xsl:apply-templates select="@PageNumber"/>
                </xsl:element>
            </xsl:if>

            <xsl:if test="File/@MimeType">
                <xsl:element name="formats">
                    <xsl:apply-templates select="File/@MimeType"/>
                </xsl:element>
            </xsl:if>

            <xsl:apply-templates select="Licence"/>

            <xsl:if test="TitleAbstract or Series">
                <xsl:element name="descriptions">
                    <xsl:apply-templates select="TitleAbstract"/>
                    <xsl:apply-templates select="Series"/>
                </xsl:element>
            </xsl:if>

            <!-- es kann in einem OPUS-XML mehrere ThesisPublisher-Elemente geben -->
            <xsl:if test="ThesisPublisher/@City">
                <xsl:element name="geoLocations">
                    <xsl:apply-templates select="ThesisPublisher/@City"/>
                </xsl:element>
            </xsl:if>

        </xsl:element>
    </xsl:template>

    <!-- FIXME:
        die Selektion (nur Dokumente mit lokalen DOIs dürfen registriert werden) erfolgt im Programmcode
        dann brauchen wir hier nicht mehr selektieren
        Frage: was passiert, wenn Dokument mehr als eine DOI hat (Altdatenbestand vor DOI-Support)
    -->
    <xsl:template match="Identifier[@Type='doi']">
        <xsl:element name="identifier">
            <xsl:attribute name="identifierType">
                <xsl:text>DOI</xsl:text>
            </xsl:attribute>
            <xsl:value-of select="@Value"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="Identifier[@Type='issn']">
        <xsl:element name="relatedIdentifier">
            <xsl:attribute name="relatedIdentifierType">
                <xsl:text>ISSN</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="relationType">
                <xsl:text>IsPartOf</xsl:text>
            </xsl:attribute>
            <xsl:value-of select="@Value"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="Identifier[@Type='isbn']">
        <xsl:element name="alternateIdentifier">
            <xsl:attribute name="alternateIdentifierType">
                <xsl:text>ISBN</xsl:text>
            </xsl:attribute>
            <xsl:value-of select="@Value"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="Identifier[@Type='urn']">
        <xsl:element name="alternateIdentifier">
            <xsl:attribute name="alternateIdentifierType">
                <xsl:text>URN</xsl:text>
            </xsl:attribute>
            <xsl:value-of select="@Value"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="TitleMain">
        <xsl:element name="title">
            <xsl:attribute name="xml:lang">
                <xsl:value-of select="php:functionString('Opus\Common\Language::getLanguageCode', @Language, 'part1')" />
            </xsl:attribute>
            <xsl:value-of select="@Value"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="TitleSub">
        <xsl:element name="title">
            <xsl:attribute name="xml:lang">
                <xsl:value-of select="php:functionString('Opus\Common\Language::getLanguageCode', @Language, 'part1')" />
            </xsl:attribute>
            <xsl:attribute name="titleType">
                <xsl:text>Subtitle</xsl:text>
            </xsl:attribute>
            <xsl:value-of select="@Value"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="TitleAbstract">
        <xsl:element name="description">
            <xsl:attribute name="xml:lang">
                <xsl:value-of select="php:functionString('Opus\Common\Language::getLanguageCode', @Language, 'part1')" />
            </xsl:attribute>
            <xsl:attribute name="descriptionType">
                <xsl:text>Abstract</xsl:text>
            </xsl:attribute>
            <xsl:value-of select="@Value"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="Series">
        <xsl:if test="../Series/@Title != ''">
            <xsl:element name="description">
                <xsl:attribute name="descriptionType">
                    <xsl:text>SeriesInformation</xsl:text>
                </xsl:attribute>
                <xsl:value-of select="../Series/@Title"/>
                <xsl:text>; </xsl:text>
                <xsl:value-of select="../Series/@Number"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>

    <xsl:template match="PersonAuthor">
        <xsl:element name="creator">
            <xsl:element name="creatorName">
                <xsl:value-of select="@LastName"/>
                <xsl:if test="@LastName and @FirstName">
                    <xsl:text>, </xsl:text>
                </xsl:if>
                <xsl:value-of select="@FirstName"/>
            </xsl:element>
            <xsl:if test="@FirstName">
                <xsl:element name="givenName">
                    <xsl:value-of select="@FirstName"/>
                </xsl:element>
            </xsl:if>
            <xsl:if test="@LastName">
                <xsl:element name="familyName">
                    <xsl:value-of select="@LastName"/>
                </xsl:element>
            </xsl:if>

            <xsl:if test="@IdentifierOrcid != ''">
                <xsl:apply-templates select="@IdentifierOrcid"/>
            </xsl:if>
        </xsl:element>
    </xsl:template>

    <xsl:template match="@CreatingCorporation">
        <xsl:if test="../@CreatingCorporation != ''">
            <xsl:element name="creator">
                <xsl:element name="creatorName">
                    <xsl:value-of select="."/>
                </xsl:element>
            </xsl:element>
        </xsl:if>
    </xsl:template>

    <xsl:template match="PersonEditor">
        <xsl:element name="contributor">
            <xsl:attribute name="contributorType">
                <xsl:text>Editor</xsl:text>
            </xsl:attribute>
            <xsl:element name="contributorName">
                <xsl:value-of select="@LastName"/>
                <xsl:text>, </xsl:text>
                <xsl:value-of select="@FirstName"/>
            </xsl:element>
            <xsl:if test="@IdentifierOrcid != ''">
                <xsl:apply-templates select="@IdentifierOrcid"/>
            </xsl:if>
        </xsl:element>
    </xsl:template>

    <xsl:template match="PersonEditor" mode="creator">
        <xsl:element name="creator">
            <xsl:element name="creatorName">
                <xsl:value-of select="@LastName"/>
                <xsl:if test="@LastName and @FirstName">
                    <xsl:text>, </xsl:text>
                </xsl:if>
                <xsl:value-of select="@FirstName"/>
                <xsl:text> (Ed.)</xsl:text>
            </xsl:element>
            <xsl:if test="@FirstName">
                <xsl:element name="givenName">
                    <xsl:value-of select="@FirstName"/>
                </xsl:element>
            </xsl:if>
            <xsl:if test="@LastName">
                <xsl:element name="familyName">
                    <xsl:value-of select="@LastName"/>
                </xsl:element>
            </xsl:if>

            <xsl:if test="@IdentifierOrcid != ''">
                <xsl:apply-templates select="@IdentifierOrcid"/>
            </xsl:if>
        </xsl:element>
    </xsl:template>

    <xsl:template match="PersonEditor" mode="creator">
        <xsl:element name="creator">
            <xsl:element name="creatorName">
                <xsl:value-of select="@LastName"/>
                <xsl:if test="@LastName and @FirstName">
                    <xsl:text>, </xsl:text>
                </xsl:if>
                <xsl:value-of select="@FirstName"/>
                <xsl:text> (Ed.)</xsl:text>
            </xsl:element>
            <xsl:if test="@FirstName">
                <xsl:element name="givenName">
                    <xsl:value-of select="@FirstName"/>
                </xsl:element>
            </xsl:if>
            <xsl:if test="@LastName">
                <xsl:element name="familyName">
                    <xsl:value-of select="@LastName"/>
                </xsl:element>
            </xsl:if>

            <xsl:if test="@IdentifierOrcid != ''">
                <xsl:element name="nameIdentifier">
                    <xsl:attribute name="schemeURI">
                        <xsl:text>https://orcid.org/</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="nameIdentifierScheme">
                        <xsl:text>ORCID</xsl:text>
                    </xsl:attribute>
                    <xsl:value-of select="@IdentifierOrcid"/>
                </xsl:element>
            </xsl:if>
        </xsl:element>
    </xsl:template>

    <xsl:template match="ServerDateCreated">
        <xsl:element name="dates">
            <xsl:element name="date">
                <xsl:attribute name="dateType">
                    <xsl:text>Created</xsl:text>
                </xsl:attribute>
                <xsl:value-of select="@Year"/>
                <xsl:text>-</xsl:text>
                <xsl:value-of select="@Month"/>
                <xsl:text>-</xsl:text>
                <xsl:value-of select="@Day"/>
            </xsl:element>
        </xsl:element>
    </xsl:template>

    <xsl:template match="ServerDatePublished">
        <xsl:element name="publicationYear">
            <xsl:value-of select="@Year"/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="@Language">
        <xsl:element name="language">
            <xsl:value-of select="php:functionString('Opus\Common\Language::getLanguageCode', ., 'part1')" />
        </xsl:element>
    </xsl:template>

    <xsl:template match="@Type">
        <xsl:element name="resourceType">
            <xsl:choose>
                <xsl:when test=".='annotation'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Annotation</xsl:text>
                </xsl:when>
                <xsl:when test=".='article'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Article</xsl:text>
                </xsl:when>
                <xsl:when test=".='bachelorthesis'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Bachelor Thesis</xsl:text>
                </xsl:when>
                <xsl:when test=".='book'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Book</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Book</xsl:text>
                </xsl:when>
                <xsl:when test=".='bookpart'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>BookChapter</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Part Of A Book</xsl:text>
                </xsl:when>
                <xsl:when test=".='conferenceabstract'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Meeting Abstract</xsl:text>
                </xsl:when>
                <xsl:when test=".='conferenceobject'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Conference Object</xsl:text>
                </xsl:when>
                <xsl:when test=".='conferencepaper'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>ConferencePaper</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Conference Paper</xsl:text>
                </xsl:when>
                <xsl:when test=".='conferenceposter'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Conference Poster</xsl:text>
                </xsl:when>
                <xsl:when test=".='conferenceproceedings'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>ConferenceProceeding</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Conference Proceedings</xsl:text>
                </xsl:when>
                <xsl:when test=".='conferenceslides'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Conference Slides</xsl:text>
                </xsl:when>
                <xsl:when test=".='contributiontoperiodical'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Contribution To A Periodical</xsl:text>
                </xsl:when>
                <xsl:when test=".='corrigendum'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Corrigendum</xsl:text>
                </xsl:when>
                <xsl:when test=".='coursematerial'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Course Material</xsl:text>
                </xsl:when>
                <xsl:when test=".='datapaper'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>DataPaper</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Data Paper</xsl:text>
                </xsl:when>
                <xsl:when test=".='diplom'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Diploma Thesis</xsl:text>
                </xsl:when>
                <xsl:when test=".='doctoralthesis'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Dissertation</xsl:text>
                    </xsl:attribute>
                    <xsl:text>PhD Thesis</xsl:text>
                </xsl:when>
                <xsl:when test=".='dynamicwebresource'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Other</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Dynamic Online Resource</xsl:text>
                </xsl:when>
                <xsl:when test=".='dynamicwebresourcepart'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Other</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Part Of A Dynamic Online Resource</xsl:text>
                </xsl:when>
                <xsl:when test=".='editedcollection'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Book</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Edited Collection</xsl:text>
                </xsl:when>
                <xsl:when test=".='editorial'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Editorial</xsl:text>
                </xsl:when>
                <xsl:when test=".='habilitation'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Dissertation</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Habilitation</xsl:text>
                </xsl:when>
                <xsl:when test=".='image'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Image</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Image</xsl:text>
                </xsl:when>
                <xsl:when test=".='lecture'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Lecture</xsl:text>
                </xsl:when>
                <xsl:when test=".='letter'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Letter To The Editor</xsl:text>
                </xsl:when>
                <xsl:when test=".='masterthesis'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Master Thesis</xsl:text>
                </xsl:when>
                <xsl:when test=".='monograph'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Book</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Monograph</xsl:text>
                </xsl:when>
                <xsl:when test=".='movingimage'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Audiovisual</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Moving Image</xsl:text>
                </xsl:when>
                <xsl:when test=".='musicalnotation'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Other</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Musical Notation</xsl:text>
                </xsl:when>
                <xsl:when test=".='periodicalpart'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Part Of A Periodical</xsl:text>
                </xsl:when>
                <xsl:when test=".='periodical'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Journal</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Periodical</xsl:text>                    
                </xsl:when>
               <xsl:when test=".='preprint'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Preprint</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Preprint</xsl:text>
                </xsl:when>
                <xsl:when test=".='report'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Report</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Report</xsl:text>
                </xsl:when>
                <xsl:when test=".='researcharticle'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Research Article</xsl:text>
                </xsl:when>
                <xsl:when test=".='researchdata'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Dataset</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Research Data</xsl:text>
                </xsl:when>
                <xsl:when test=".='review'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Recension</xsl:text>
                </xsl:when>
                <xsl:when test=".='reviewarticle'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Review Article</xsl:text>
                </xsl:when>
                <xsl:when test=".='software'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Software</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Software</xsl:text>
                </xsl:when>
                <xsl:when test=".='softwarepaper'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>JournalArticle</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Software Paper</xsl:text>
                </xsl:when>
                <xsl:when test=".='sound'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Sound</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Sound</xsl:text>
                </xsl:when>
                <xsl:when test=".='sourceedition'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Book</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Source Edition</xsl:text>
                </xsl:when>
                <xsl:when test=".='studythesis'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Study Thesis</xsl:text>
                </xsl:when>
                <xsl:when test=".='website'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Other</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Website</xsl:text>
                </xsl:when>
                <xsl:when test=".='workingpaper'">                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Text</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Working Paper</xsl:text>
                </xsl:when>
                <xsl:otherwise>                    
                    <xsl:attribute name="resourceTypeGeneral">
                        <xsl:text>Other</xsl:text>
                    </xsl:attribute>
                    <xsl:text>Other</xsl:text>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:element>
    </xsl:template>

    <xsl:template match="Licence">
        <xsl:element name="rightsList">
            <xsl:element name="rights">
                <xsl:attribute name="rightsURI">
                    <xsl:value-of select="@LinkLicence"/>
                </xsl:attribute>
                <xsl:value-of select="@NameLong"/>
            </xsl:element>
        </xsl:element>
    </xsl:template>

    <xsl:template match="Collection">
        <xsl:if test="@RoleName= 'ddc'">
            <xsl:element name="subject">
                <xsl:attribute name="xml:lang">
                    <xsl:text>de</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="schemeURI">
                    <xsl:text>https://dewey.info/</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="subjectScheme">
                    <xsl:text>dewey</xsl:text>
                </xsl:attribute>
                <xsl:if test="@Number">
                    <xsl:value-of select="@Number"/>
                    <xsl:text> </xsl:text>
                </xsl:if>
                <xsl:value-of select="@Name"/>
            </xsl:element>
        </xsl:if>
    </xsl:template>

    <xsl:template match="ThesisPublisher/@City">
        <xsl:element name="geoLocation">
            <xsl:element name="geoLocationPlace">
                <xsl:value-of select="."/>
            </xsl:element>
        </xsl:element>
    </xsl:template>

    <xsl:template match="File/@FileSize">
        <xsl:element name="size">
            <xsl:value-of select="round(. div 1024)"/>
            <xsl:text> KB</xsl:text>
        </xsl:element>
    </xsl:template>

    <xsl:template match="@PageNumber">
        <xsl:element name="size">
            <xsl:value-of select="."/>
            <xsl:text> pages</xsl:text>
        </xsl:element>
    </xsl:template>

    <xsl:template match="File/@MimeType">
        <xsl:element name="format">
            <xsl:value-of select="."/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="@IdentifierOrcid">
        <xsl:element name="nameIdentifier">
            <xsl:attribute name="schemeURI">
                <xsl:text>https://orcid.org/</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="nameIdentifierScheme">
                <xsl:text>ORCID</xsl:text>
            </xsl:attribute>
            <xsl:value-of select="."/>
        </xsl:element>
    </xsl:template>

</xsl:stylesheet>
