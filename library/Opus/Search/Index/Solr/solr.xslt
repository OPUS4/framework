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
 * @category    Framework
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
-->

<!--
/**
 * @category    Framework
 * @package     Opus_Search
 */
-->

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

    <xsl:output method="xml" indent="yes" />

    <!-- Suppress output for all elements that don't have an explicit template. -->
    <xsl:template match="*" />
    
    <xsl:template match="/">
        <xsl:element name="add">
            <xsl:element name="doc">

                <!-- id -->
                <xsl:element name="field">
                    <xsl:attribute name="name">id</xsl:attribute>
                    <xsl:value-of select="/Opus/Opus_Model_Filter/@Id" />
                </xsl:element>

                <!-- year -->
                <xsl:element name="field">
                    <xsl:attribute name="name">year</xsl:attribute>
                    <xsl:value-of select="/Opus/Opus_Model_Filter/PublishedDate/@Year" />
                </xsl:element>

                <!-- language -->
                <xsl:variable name="language" select="/Opus/Opus_Model_Filter/@Language" />
                <xsl:element name="field">
                    <xsl:attribute name="name">language</xsl:attribute>
                    <xsl:value-of select="$language" />
                </xsl:element>            

                <!-- title / title_output -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/TitleMain">
                    <xsl:element name="field">
                        <xsl:attribute name="name">title</xsl:attribute>
                        <xsl:value-of select="@Value" />
                    </xsl:element>
                    <xsl:if test="@Language = $language">
                        <xsl:element name="field">
                            <xsl:attribute name="name">title_output</xsl:attribute>
                            <xsl:value-of select="@Value" />
                        </xsl:element>
                    </xsl:if>
                </xsl:for-each>

                <!-- abstract / abstract_output -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/TitleAbstract">
                    <xsl:element name="field">
                        <xsl:attribute name="name">abstract</xsl:attribute>
                        <xsl:value-of select="@Value" />
                    </xsl:element>
                    <xsl:if test="@Language = $language">
                        <xsl:element name="field">
                            <xsl:attribute name="name">abstract_output</xsl:attribute>
                            <xsl:value-of select="@Value" />
                        </xsl:element>
                    </xsl:if>
                </xsl:for-each>
                
                <!-- author -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/PersonAuthor">
                    <xsl:element name="field">
                        <xsl:attribute name="name">author</xsl:attribute>
                        <xsl:value-of select="@FirstName" />
                        <xsl:text> </xsl:text>
                        <xsl:value-of select="@LastName" />
                    </xsl:element>
                </xsl:for-each>

                <!-- author_sort -->
                <xsl:element name="field">
                    <xsl:attribute name="name">author_sort</xsl:attribute>
                    <xsl:for-each select="/Opus/Opus_Model_Filter/PersonAuthor">
                        <xsl:value-of select="@LastName" />
                        <xsl:text> </xsl:text>
                        <xsl:value-of select="@FirstName" />
                        <xsl:text> </xsl:text>
                    </xsl:for-each>
                </xsl:element>

                <!-- fulltext -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/Fulltext_Index">
                    <xsl:element name="field">
                        <xsl:attribute name="name">fulltext</xsl:attribute>
                        <xsl:value-of select="." />
                    </xsl:element>
                </xsl:for-each>

                <!-- has fulltext -->
                <xsl:element name="field">
                    <xsl:attribute name="name">has_fulltext</xsl:attribute>
                    <xsl:value-of select="/Opus/Opus_Model_Filter/Has_Fulltext" />
                </xsl:element>

                <!-- persons: PersonSubmitter, PersonsReferee, PersonEditor, PersonTranslator, PersonContributor, PersonAdvisor, PersonOther -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/*">
                    <xsl:if test="substring(name(), 1, 6)='Person'">
                        <xsl:if test="name()!='PersonAuthor'">
                            <xsl:element name="field">
                                <xsl:attribute name="name">persons</xsl:attribute>
                                <xsl:value-of select="@Name" />
                            </xsl:element>
                        </xsl:if>
                    </xsl:if>
                </xsl:for-each>

                <!-- referee -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/PersonReferee">
                    <xsl:element name="field">
                        <xsl:attribute name="name">referee</xsl:attribute>
                        <xsl:value-of select="@FirstName" />
                        <xsl:text> </xsl:text>
                        <xsl:value-of select="@LastName" />
                    </xsl:element>
                </xsl:for-each>

                <!-- doctype -->
                <xsl:element name="field">
                    <xsl:attribute name="name">doctype</xsl:attribute>
                    <xsl:value-of select="/Opus/Opus_Model_Filter/@Type" />
                </xsl:element>

                <!-- subject (swd) -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/SubjectSwd">
                    <xsl:element name="field">
                        <xsl:attribute name="name">subject</xsl:attribute>
                        <xsl:value-of select="@Value" />
                    </xsl:element>
                </xsl:for-each>

                <!-- subject (uncontrolled) -->
                <xsl:for-each select="/Opus/Opus_Model_Filter/SubjectUncontrolled">
                    <xsl:element name="field">
                        <xsl:attribute name="name">subject</xsl:attribute>
                        <xsl:value-of select="@Value" />
                    </xsl:element>
                </xsl:for-each>

                <!-- Bibliographie -->                
                <xsl:element name="field">
                    <xsl:attribute name="name">belongs_to_bibliography</xsl:attribute>
                    <xsl:value-of select="/Opus/Opus_Model_Filter/@BelongsToBibliography" />
                </xsl:element>

                <!-- TODO: institute -->
                <!--xsl:for-each select="/Opus/Opus_Model_Filter/Collection">
                    <xsl:if test="@RoleId = 1">
                        <xsl:element name="field">
                            <xsl:attribute name="name">institute</xsl:attribute>
                            <xsl:value-of select="@Name" />
                        </xsl:element>
                    </xsl:if>
                 </xsl:for-each-->

                <!-- TODO: CreatingCorporation, ContributingCorporation -->

                <!-- TODO: PublisherName, PublisherPlace -->

                <!-- TODO: TitleParent -->

            </xsl:element>
        </xsl:element>
    </xsl:template>
</xsl:stylesheet>