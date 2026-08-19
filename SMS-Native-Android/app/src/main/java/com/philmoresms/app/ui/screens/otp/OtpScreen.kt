package com.philmoresms.app.ui.screens.otp

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.*
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.components.LabeledDropdown
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await
import kotlin.random.Random

class OtpViewModel : ViewModel() {
    var senderIds by mutableStateOf<List<SenderId>>(emptyList())
    var senderId by mutableStateOf("")
    var recipients by mutableStateOf("")
    var otp by mutableStateOf(Random.nextInt(100000, 999999).toString())
    var templateCode by mutableStateOf("")
    var templates by mutableStateOf<List<OtpTemplate>>(emptyList())
    var sending by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    var showRegister by mutableStateOf(false)
    var newSenderId by mutableStateOf("")
    var sampleMessage by mutableStateOf("")
    var registering by mutableStateOf(false)

    init { load() }

    fun load() {
        viewModelScope.launch {
            try {
                val list = RetrofitClient.apiService.getSenderIds().await().senderIds ?: emptyList()
                senderIds = list.filter { it.type == "otp" && it.status?.lowercase() == "approved" }
                if (senderIds.isNotEmpty() && senderId.isEmpty()) senderId = senderIds.first().senderId ?: ""
                templates = RetrofitClient.apiService.getOtpTemplates().await().templates ?: emptyList()
            } catch (_: Exception) { /* sender IDs / templates are optional */ }
        }
    }

    fun registerSender() {
        error = null
        success = null
        registering = true
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.requestSenderId(newSenderId, sampleMessage, "otp").await()
                success = res.message ?: "OTP Sender ID request submitted"
                newSenderId = ""
                sampleMessage = ""
                showRegister = false
                load()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to submit request"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                registering = false
            }
        }
    }

    fun regenerate() {
        otp = Random.nextInt(100000, 999999).toString()
    }

    val recipientCount: Int get() = SmsRules.recipientCount(recipients)

    fun send() {
        error = null
        success = null
        sending = true
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.sendOtp(senderId, recipients, otp, templateCode).await()
                success = res.message ?: "OTP sent successfully"
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to send OTP"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                sending = false
            }
        }
    }
}

@Composable
fun OtpScreen(navController: NavController) {
    val viewModel: OtpViewModel = viewModel()

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Send OTP", onBack = { navController.popBackStack() })

        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)
        ) {
            // OTP Sender ID (approved, OTP type only)
            LabeledDropdown(
                label = "OTP Sender ID (approved)",
                selected = viewModel.senderId,
                options = viewModel.senderIds.mapNotNull { it.senderId },
                placeholder = "Select an OTP Sender ID"
            ) { viewModel.senderId = it }

            if (viewModel.senderIds.isEmpty()) {
                Text("No approved OTP Sender ID yet.", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 8.dp))
            }

            // Register a new OTP sender ID
            TextButton(onClick = { viewModel.showRegister = !viewModel.showRegister }, modifier = Modifier.padding(bottom = 8.dp)) {
                Text(if (viewModel.showRegister) "Cancel registration" else "+ Register new OTP Sender ID", color = Primary, fontWeight = FontWeight.Bold)
            }
            if (viewModel.showRegister) {
                FintechInput("New Sender ID (max 11 chars)", viewModel.newSenderId, { viewModel.newSenderId = it })
                FintechInput("Sample Message", viewModel.sampleMessage, { viewModel.sampleMessage = it })
                FintechButton(if (viewModel.registering) "Submitting..." else "Submit OTP Sender ID", { viewModel.registerSender() }, backgroundColor = OtpColor)
            }

            FintechInput("Recipients", viewModel.recipients, { viewModel.recipients = it }, placeholder = "Numbers separated by commas, spaces, or new lines")
            Text(
                "Recipients: ${viewModel.recipientCount}",
                fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold,
                modifier = Modifier.align(Alignment.End).padding(bottom = 8.dp)
            )

            // OTP display + regenerate
            Surface(
                modifier = Modifier.fillMaxWidth().padding(bottom = 20.dp),
                shape = RoundedCornerShape(14.dp),
                color = OtpColor.copy(alpha = 0.1f)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column {
                        Text("ONE-TIME PIN", fontSize = 11.sp, color = TextSecondary, fontWeight = FontWeight.Bold)
                        Text(viewModel.otp, fontSize = 28.sp, fontWeight = FontWeight.ExtraBold, color = OtpColor, letterSpacing = 6.sp)
                    }
                    Button(onClick = { viewModel.regenerate() }, colors = ButtonDefaults.buttonColors(containerColor = OtpColor)) {
                        Text("Regenerate", color = Color.White)
                    }
                }
            }

            // Template dropdown
            Text("Template (optional)", fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 8.dp))
            var expanded by remember { mutableStateOf(false) }
            Box(modifier = Modifier.fillMaxWidth().padding(bottom = 20.dp)) {
                OutlinedButton(onClick = { expanded = true }, modifier = Modifier.fillMaxWidth()) {
                    Text(
                        viewModel.templates.firstOrNull { it.templateCode == viewModel.templateCode }?.templateName ?: "Select template",
                        color = TextPrimary
                    )
                }
                DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                    viewModel.templates.forEach { tpl ->
                        DropdownMenuItem(
                            text = { Text(tpl.templateName ?: tpl.templateCode ?: "") },
                            onClick = {
                                viewModel.templateCode = tpl.templateCode ?: ""
                                expanded = false
                            }
                        )
                    }
                }
            }

            if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))
            if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))

            FintechButton(if (viewModel.sending) "Sending..." else "Send OTP", { viewModel.send() }, backgroundColor = OtpColor)
            Spacer(Modifier.height(24.dp))
        }
    }
}

