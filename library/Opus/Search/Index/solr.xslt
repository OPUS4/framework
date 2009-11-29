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

    <xsl:param name="fulltext" />
    <xsl:param name="source" />

    <!--
    Suppress output for all elements that don't have an explicit template.
    -->
    <xsl:template match="*" />
    <xsl:template match="*" mode="oai_dc" />

    <!--create the head of oai response  -->
    <xsl:template match="/">
    <xsl:element name="add">
        <xsl:element name="doc">
            <xsl:element name="field">
                <xsl:attribute name="name">id</xsl:attribute>
                <xsl:value-of select="$source" />
                <xsl:value-of select="/Opus/Opus_Model_Filter/@Id" />
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">source</xsl:attribute>
                <xsl:value-of select="$source" />
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">docid</xsl:attribute>
                <xsl:value-of select="/Opus/Opus_Model_Filter/@Id" />
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">year</xsl:attribute>
                <xsl:value-of select="/Opus/Opus_Model_Filter/@CompletedYear" />
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">urn</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/IdentifierUrn">
                    <xsl:value-of select="@Value" />
                    <xsl:text> </xsl:text>
                </xsl:for-each>
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">isbn</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/IdentifierIsbn">
                    <xsl:value-of select="@Value" />
                    <xsl:text> </xsl:text>
                </xsl:for-each>
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">abstract</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/TitleAbstract">
                    <xsl:value-of select="@Value" />
                    <xsl:text> </xsl:text>
                </xsl:for-each>
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">title</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/TitleMain">
                    <xsl:value-of select="@Value" />
                    <xsl:text> </xsl:text>
                </xsl:for-each>
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">author</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/PersonAuthor">
                    <xsl:value-of select="@Name" />
                    <xsl:text> </xsl:text>
                </xsl:for-each>
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">fulltext</xsl:attribute>
                <xsl:value-of select="$fulltext" />
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">persons</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/*">
                    <xsl:if test="substring(name(), 1, 6)='Person'">
                        <xsl:if test="name()!='PersonAuthor'">
                            <xsl:value-of select="@Name" />
                            <xsl:text> </xsl:text>
                        </xsl:if>
                    </xsl:if>
                </xsl:for-each>
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">language</xsl:attribute>
                <xsl:value-of select="/Opus/Opus_Model_Filter/@Language" />
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">subject</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/Collection">
                    <xsl:if test="@RoleId != 1">
                        <xsl:value-of select="@Name" />
                        <xsl:text> </xsl:text>
                    </xsl:if>
                </xsl:for-each>
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">doctype</xsl:attribute>
                <xsl:value-of select="/Opus/Opus_Model_Filter/@Type" />
            </xsl:element>
            <xsl:element name="field">
                <xsl:attribute name="name">institute</xsl:attribute>
                <xsl:for-each select="/Opus/Opus_Model_Filter/Collection">
                    <xsl:if test="@RoleId = 1">
                        <xsl:value-of select="@Name" />
                        <xsl:text> </xsl:text>
                    </xsl:if>
                </xsl:for-each>
            </xsl:element>
        </xsl:element>
    </xsl:element>
    </xsl:template>

</xsl:stylesheet>
